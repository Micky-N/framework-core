<?php

namespace MkyCore;

use MkyCore\Exceptions\Cache\CacheException;

class Cache
{

    /**
     * The path to the cache file folder
     *
     * @var string
     */
    private $cachepath = 'cache/';

    /**
     * The name of the default cache file
     *
     * @var string
     */
    private $cachename = 'default';

    /**
     * The cache file extension
     *
     * @var string
     */
    private $extension = '.cache';

    /**
     * Default constructor
     *
     * @param string|array [optional] $config
     */
    public function __construct(string|array $config = null)
    {
        if (true === isset($config)) {
            if (is_string($config)) {
                $this->setCache($config);
            } else if (is_array($config)) {
                $this->setCache($config['name']);
                $this->setCachePath($config['path']);
                $this->setExtension($config['extension']);
            }
        }
    }

    /**
     * Check whether data accociated with a key
     *
     * @param string $key
     * @return boolean
     */
    public function isCached(string $key): bool
    {
        if (false != $this->loadCache()) {
            $cachedData = $this->loadCache();
            return isset($cachedData[$key]['data']);
        }
    }

    /**
     * Store data in the cache
     *
     * @param string $key
     * @param mixed $data
     * @param int [optional] $expiration
     * @return static
     */
    public function store(string $key, mixed $data, int $expiration = 0): static
    {
        $storeData = [
            'time'   => time(),
            'expire' => time() + $expiration,
            'data'   => serialize($data)
        ];
        $dataArray = $this->loadCache();
        if (true === is_array($dataArray)) {
            $dataArray[$key] = $storeData;
        } else {
            $dataArray = [$key => $storeData];
        }
        $cacheData = json_encode($dataArray);
        file_put_contents($this->getCacheDir(), $cacheData);
        return $this;
    }

    /**
     * Retrieve cached data by its key
     * 
     * @param string $key
     * @param boolean [optional] $timestamp
     * @return mixed
     */
    public function retrieve(string $key, bool $timestamp = false): mixed
    {
        $cachedData = $this->loadCache();
        (false === $timestamp) ? $type = 'data' : $type = 'time';
        if (!isset($cachedData[$key][$type])) return null;
        return unserialize($cachedData[$key][$type]);
    }

    /**
     * Retrieve all cached data
     * 
     * @param boolean [optional] $meta
     * @return array
     */
    public function retrieveAll(bool $meta = false): array
    {
        $cachedData = $this->loadCache();
        if ($meta === false) {
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
     * Erase cached entry by its key
     * 
     * @param string $key
     * @return static
     */
    public function erase(string $key): static
    {
        $cacheData = $this->loadCache();
        if (true === is_array($cacheData)) {
            if (true === isset($cacheData[$key])) {
                unset($cacheData[$key]);
                $cacheData = json_encode($cacheData);
                file_put_contents($this->getCacheDir(), $cacheData);
            } else {
                throw new CacheException("Error: erase() - Key '{$key}' not found.");
            }
        }
        return $this;
    }

    /**
     * Erase all expired entries
     * 
     * @return int
     */
    public function eraseExpired(): int
    {
        $cacheData = $this->loadCache();
        if (true === is_array($cacheData)) {
            $counter = 0;
            foreach ($cacheData as $key => $entry) {
                if (true === $this->checkExpired($entry['expire'])) {
                    unset($cacheData[$key]);
                    $counter++;
                }
            }
            if ($counter > 0) {
                $cacheData = json_encode($cacheData);
                file_put_contents($this->getCacheDir(), $cacheData);
            }
            return $counter;
        }
    }

    /**
     * Erase all cached entries
     * 
     * @return static
     */
    public function eraseAll(): static
    {
        $cacheDir = $this->getCacheDir();
        if (true === file_exists($cacheDir)) {
            $cacheFile = fopen($cacheDir, 'w');
            fclose($cacheFile);
        }
        return $this;
    }

    /**
     * Load appointed cache
     * 
     * @return mixed
     */
    private function loadCache(): mixed
    {
        if (true === file_exists($this->getCacheDir())) {
            $file = file_get_contents($this->getCacheDir());
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
    public function getCacheDir(): string
    {
        if (true === $this->checkCacheDir()) {
            $filename = $this->getCache();
            $filename = preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($filename));
            return $this->getCachePath() . $this->getHash($filename) . $this->getExtension();
        }
    }

    /**
     * Get the filename hash
     * 
     * @return string
     */
    private function getHash(string $filename): string
    {
        return sha1($filename);
    }

    /**
     * Check whether a timestamp is still in the duration 
     * 
     * @param int $timestamp
     * @param int $expiration
     * @return boolean
     */
    private function checkExpired(int $expiration): bool
    {
        if ($expiration == 0) {
            return true;
        }
        return $expiration - time() < 0;
    }

    /**
     * Check if a writable cache directory exists and if not create a new one
     * 
     * @return boolean
     */
    private function checkCacheDir(): bool
    {
        if (!is_dir($this->getCachePath()) && !mkdir($this->getCachePath(), 0775, true)) {
            throw new CacheException('Unable to create cache directory ' . $this->getCachePath());
        } elseif (!is_readable($this->getCachePath()) || !is_writable($this->getCachePath())) {
            if (!chmod($this->getCachePath(), 0775)) {
                throw new CacheException($this->getCachePath() . ' must be readable and writeable');
            }
        }
        return true;
    }

    /**
     * Cache path Setter
     * 
     * @param string $path
     * @return static
     */
    public function setCachePath(string $path): static
    {
        $this->cachepath = $path;
        return $this;
    }

    /**
     * Cache path Getter
     * 
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->cachepath;
    }

    /**
     * Cache name Setter
     * 
     * @param string $name
     * @return static
     */
    public function setCache(string $name): static
    {
        $this->cachename = $name;
        return $this;
    }

    /**
     * Cache name Getter
     * 
     * @return string
     */
    public function getCache(): string
    {
        return $this->cachename;
    }

    /**
     * Cache file extension Setter
     * 
     * @param string $ext
     * @return static
     */
    public function setExtension(string $ext): static
    {
        $this->extension = $ext;
        return $this;
    }

    /**
     * Cache file extension Getter
     * 
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }
}
