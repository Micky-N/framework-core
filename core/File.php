<?php

namespace MkyCore;

use GuzzleHttp\Psr7\UploadedFile;
use League\Flysystem\FilesystemException;

class File extends UploadedFile
{

    public static function makePath(array $path, bool $withCheck = false): string|bool
    {
        $path = array_filter($path, fn($p) => $p);
        $path = array_map(fn($p) => trim($p, '\/'), $path);
        $res = join(DIRECTORY_SEPARATOR, $path);
        if ($withCheck) {
            if(!file_exists($res) && !is_dir($res)){
                return false;
            }
        }
        return $res;
    }

    public static function makeNamespace(string $file, bool $withCheck = false): string|bool
    {
        $file = str_replace([app()->get('path:app'), '.php', '/'], ['App', '', '\\'], $file);
        $file = trim($file, '\/');
        if ($withCheck) {
            if(!class_exists($file)){
                return false;
            }
        }
        return $file;
    }

    public function extension(): bool|string
    {
        $mediatype = explode('/', $this->getClientMediaType());
        return end($mediatype);
    }

    public function filename(): array|string|null
    {
        return str_replace('.' . $this->realExtension(), '', $this->getClientFilename());
    }

    public function realExtension(): bool|string
    {
        $clientFilename = explode('.', $this->getClientFilename(), 2);
        return end($clientFilename);
    }

    /**
     * Return true if there is no upload error
     */
    public function isOk(): bool
    {
        return $this->getError() === UPLOAD_ERR_OK;
    }

    /**
     * @throws FilesystemException
     */
    public function store(string|array $path, string $rename = null): bool
    {
        $space = null;
        if (is_array($path)) {
            $space = $path['space'] ?? null;
            $path = $path['path'];
        }
        $name = $newFileName ?? $this->getClientFilename();
        $location = trim($path, '\/') . DIRECTORY_SEPARATOR . $name;
        if ($space) {
            \MkyCore\Facades\FileManager::use($space)->write($location, $this->getStream());
        } else {
            \MkyCore\Facades\FileManager::write($location, $this->getStream());
        }
        return true;
    }
}