<?php

namespace MkyCore;

use GuzzleHttp\Psr7\UploadedFile;
use League\Flysystem\FilesystemException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class File extends UploadedFile
{

    /**
     * Make path from array, can check if exists
     * return path if true
     *
     * @param array $path
     * @param bool $withCheck
     * @return string|false
     */
    public static function makePath(array $path, bool $withCheck = false): string|false
    {
        $path = array_filter($path, fn($p) => $p);
        $path = array_map(fn($p) => trim($p, '\/'), $path);
        $res = join(DIRECTORY_SEPARATOR, $path);
        if ($withCheck) {
            if (!file_exists($res) && !is_dir($res)) {
                return false;
            }
        }
        return $res;
    }

    /**
     * Make namespace from array, can check if exists
     * return path if true
     *
     * @param string $file
     * @param bool $withCheck
     * @return string|false
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public static function makeNamespace(string $file, bool $withCheck = false): string|false
    {
        $file = str_replace([app()->get('path:app'), '.php', '/'], ['App', '', '\\'], $file);
        $file = trim($file, '\/');
        if ($withCheck) {
            if (!class_exists($file)) {
                return false;
            }
        }
        return $file;
    }

    /**
     * Get file extension
     *
     * @return bool|string
     */
    public function extension(): false|string
    {
        $mediaType = explode('/', $this->getClientMediaType());
        return end($mediaType);
    }

    /**
     * Get file name
     *
     * @return string|null
     */
    public function filename(): string|null
    {
        return $this->getClientFilename() ? str_replace('.' . $this->realExtension(), '', $this->getClientFilename()) : null;
    }

    /**
     * Get real extension
     * @return false|string
     */
    public function realExtension(): false|string
    {
        $clientFilename = explode('.', $this->getClientFilename(), 2);
        return end($clientFilename);
    }

    /**
     * Return true if there is no upload error
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->getError() === UPLOAD_ERR_OK;
    }

    /**
     * Store file to file system
     *
     * @param string|array $path
     * @param string|null $rename
     * @return bool
     * @throws FilesystemException
     */
    public function store(string|array $path, string $rename = null): bool
    {
        $space = null;
        if (is_array($path)) {
            $space = $path['space'] ?? null;
            $path = $path['path'];
        }
        $name = $rename ?? $this->getClientFilename();
        $location = trim($path, '\/') . DIRECTORY_SEPARATOR . $name;
        if ($space) {
            \MkyCore\Facades\FileManager::use($space)->write($location, $this->getStream());
        } else {
            \MkyCore\Facades\FileManager::write($location, $this->getStream());
        }
        return true;
    }
}