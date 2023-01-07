<?php

namespace MkyCore\FileSystems;

use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use League\Flysystem\Visibility;
use MkyCore\Facades\Request;

class LocalFileSystem extends Filesystem
{

    private string $root;
    /**
     * Construction of localFileSystem with filesystem config
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        // SETUP
        $this->root = $config['root'];
        $permissionMap = [
            'file' => [
                'public' => 0640,
                'private' => 0604,
            ],
            'dir' => [
                'public' => 0740,
                'private' => 7604,
            ]
        ];
        $defaultForDirectories = $config['visibility'] ?? Visibility::PRIVATE;
        $tmpUrl = isset($config['temporary_url']) && !$config['temporary_url'] ? null : new class() implements TemporaryUrlGenerator {
            public function temporaryUrl(
                string            $path,
                DateTimeInterface $expiresAt,
                Config            $config
            ): string
            {
                $default = Request::baseUri() . '/tmp';
                $base = trim($config->get('url', $default), '/') . '/';
                return $base . trim($path, '/') . '?expires_at=' . $expiresAt->format('U');
            }
        };
        $url = isset($config['url']) && !$config['url'] ? null : new class() implements PublicUrlGenerator {
            public function publicUrl(string $path, Config $config): string
            {
                $default = Request::baseUri() . '/tmp';
                $base = trim($config->get('url', $default), '/') . '/';
                return $base . trim($path, '/');
            }
        };
        parent::__construct(
            adapter: new LocalFilesystemAdapter(
                $config['root'],
                PortableVisibilityConverter::fromArray($permissionMap, $defaultForDirectories)
            ),
            config: $config,
            pathNormalizer: $pathNormalizer ?? null,
            publicUrlGenerator: $url,
            temporaryUrlGenerator: $tmpUrl
        );
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Create symlink
     *
     * @param string $target
     * @param string $link
     * @return bool
     */
    public function link(string $target, string $link): bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            symlink($target, $link);
        }

        $mode = is_dir($target) ? 'J' : 'H';
        return exec("mklink /$mode " . escapeshellarg($link) . ' ' . escapeshellarg($target)) !== false;
    }
}