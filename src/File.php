<?php

namespace MkyCore;

use GuzzleHttp\Psr7\UploadedFile;

class File extends UploadedFile
{

    public function extension()
    {
        $mediatype = explode('/', $this->getClientMediaType());
        return end($mediatype);
    }

    public function filename()
    {
        return str_replace('.' . $this->realExtension(), '', $this->getClientFilename());
    }

    public function realExtension()
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