<?php

namespace MkyCore;

use League\Flysystem\FilesystemException;
use MkyCore\Exceptions\Cache\CacheException;

class Cache
{

    /**
     * The cache file extension
     *
     * @var string
     */
    private const EXTENSION = '.cache';

    private const TIME = 'time';

    private const DATA = 'data';

    /**
     * The name of the default cache file
     *
     * @var string
     */
    private string $cacheName;

    private FileManager $fileManager;

    /**
     * Default constructor
     *
     * @param string $name
     * @param array $config
     */
    public function __construct(string $name, array $config)
    {
        $this->cacheName = $name;
        $driver = $config['driver'];
        $this->fileManager = $this->{$driver . 'Adapter'}($name, $config);
    }

    /**
     * Check if key exists and not expires
     *
     * @param string $key
     * @return boolean
     * @throws FilesystemException
     */
    public function has(string $key): bool
    {
        if (!$cachedData = $this->loadCache()) {
            return false;
        }
        return isset($cachedData[$key]['data']) && $this->isExpired($key);
    }

    /**
     * Load appointed cache
     *
     * @return mixed
     * @throws FilesystemException
     */
    private function loadCache(): mixed
    {
        if ($this->fileManager->fileExists($this->getCacheFile())) {
            $file = $this->fileManager->read($this->getCacheFile());
            return json_decode($file, true);
        } else {
            return false;
        }
    }

    /**
     * Get the cache directory path
     *
     * @return string
     */
    public function getCacheFile(): string
    {
        $namespace = $this->getCache();
        $filename = preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($namespace));
        return $namespace . '://' . $this->getHash($filename) . self::EXTENSION;
    }

    /**
     * Cache name Getter
     *
     * @return string
     */
    public function getCache(): string
    {
        return $this->cacheName;
    }

    /**
     * Get the filename hash
     *
     * @param string $filename
     * @return string
     */
    private function getHash(string $filename): string
    {
        return sha1(base64_encode(env('APP_KEY', 'mky') . $filename));
    }

    /**
     * Check whether a timestamp is still in the duration
     *
     * @param string $key
     * @return boolean
     * @throws FilesystemException
     */
    public function isExpired(string $key): bool
    {
        $cacheData = $this->loadCache();
        if (!$cacheData) {
            return false;
        }
        $cache = $cacheData[$key];
        if (!$cache) {
            return false;
        }
        $expiration = $cache['expiration'] ?? 0;
        if ($expiration == 0) {
            return true;
        }
        return $expiration - time() < 0;
    }

    /**
     * Retrieve cached data by its key
     *
     * @param string $key
     * @param boolean $timestamp [optional]
     * @return mixed
     * @throws CacheException
     * @throws FilesystemException
     */
    public function get(string $key, bool $timestamp = false): mixed
    {
        if ($this->isExpired($key)) {
            $this->remove($key);
            return null;
        }

        $cachedData = $this->loadCache();
        if (!isset($cachedData[$key])) {
            return null;
        }
        (false === $timestamp) ? $type = self::DATA : $type = self::TIME;
        $res = $cachedData[$key][$type];
        if (!$this->isSerialized($res)) {
            $res = serialize($res);
        }
        return unserialize($res);
    }

    /**
     * Erase cached entry by its key
     *
     * @param string $key
     * @return static
     * @throws CacheException
     * @throws FilesystemException
     */
    public function remove(string $key): static
    {
        $cacheData = $this->loadCache();
        if (true === is_array($cacheData)) {
            if (true === isset($cacheData[$key])) {
                unset($cacheData[$key]);
                $cacheData = json_encode($cacheData);
                $this->fileManager->write($this->getCacheFile(), $cacheData);
            } else {
                throw new CacheException("Error: erase() - Key '$key' not found.");
            }
        }
        return $this;
    }

    /**
     * Check if data is serialized
     *
     * @param $string
     * @return bool
     */
    private function isSerialized($string): bool
    {
        return (@unserialize($string) !== false);
    }

    /**
     * Store data in the cache
     *
     * @param string $key
     * @param mixed $data
     * @param int $expiration
     * @return static
     * @throws FilesystemException
     */
    public function store(string $key, mixed $data, int $expiration = 3600): static
    {
        $storeData = [
            'time' => time(),
            'expiration' => time() + $expiration,
            'data' => serialize($data)
        ];
        $dataArray = $this->loadCache();
        if (true === is_array($dataArray)) {
            $dataArray[$key] = $storeData;
        } else {
            $dataArray = [$key => $storeData];
        }
        $cacheData = json_encode($dataArray);
        $this->fileManager->write($this->getCacheFile(), $cacheData);
        return $this;
    }

    /**
     * Retrieve all cached data
     *
     * @param boolean $meta [optional]
     * @return array
     * @throws FilesystemException
     */
    public function getAll(bool $meta = false): array
    {
        $this->removeExpired();
        $cachedData = $this->loadCache();
        if (!$meta) {
            $results = [];
            if ($cachedData) {
                foreach ($cachedData as $k => $v) {
                    $results[$k] = unserialize($v['data']);
                }
            }
            $cachedData = $results;
        }
        return $cachedData;
    }

    /**
     * Erase all expired entries
     *
     * @return int
     * @throws FilesystemException
     */
    public function removeExpired(): int
    {
        $counter = 0;
        $cacheData = $this->loadCache();
        if (is_array($cacheData)) {
            foreach ($cacheData as $key => $entry) {
                if ($this->isExpired($key)) {
                    unset($cacheData[$key]);
                    $counter++;
                }
            }
            if ($counter > 0) {
                $cacheData = json_encode($cacheData);
                file_put_contents($this->getCacheFile(), $cacheData);
            }
        }
        return $counter;
    }

    /**
     * Erase all cached entries
     *
     * @return static
     */
    public function removeAll(): static
    {
        $cacheDir = $this->getCacheFile();
        if (true === file_exists($cacheDir)) {
            $cacheFile = fopen($cacheDir, 'w');
            fclose($cacheFile);
        }
        return $this;
    }

    /**
     * Cache name Setter
     *
     * @param string $name
     * @param array $config
     * @return static
     */
    public function use(string $name, array $config = []): static
    {
        if (!$config) {
            $config = config('cache.spaces.' . $name, []);
        }
        return new static($name, $config);
    }

    /**
     * Get cache full path
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->fileManager->getFilesystems()[$this->getCache()]->getRoot());
    }

    /**
     * Local adapter construction
     *
     * @param string $space
     * @param array $config
     * @return FileManager
     */
    private function localAdapter(string $space, array $config): FileManager
    {
        $config['root'] = $config['path'];
        $config['url'] = $config['temporary_url'] = false;
        return new FileManager($space, $config);
    }

}
