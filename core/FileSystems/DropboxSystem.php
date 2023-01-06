<?php

namespace MkyCore\FileSystems;

use Kunnu\Dropbox\DropboxApp;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use League\Flysystem\Visibility;
use MkyCore\Facades\Request;

class DropboxSystem extends Filesystem
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
        $app = new DropboxApp($config['client_id'], $config["client_secret"], $config['access_token'] ?? null);
        $dropbox = new Dropbox($app);
        $this->root = $config['root'];
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
        );
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }
}