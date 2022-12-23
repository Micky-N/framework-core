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

    /**
     * Construction of localFileSystem with filesystem config
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        // SETUP
        $config = array_replace_recursive([
            'public_url' => trim($config['url'] ?? Request::baseUri(), '/') . '/',
            'temporary_url' => trim($config['url'] ?? Request::baseUri(), '/') . '/tmp/'
        ], $config);
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
        parent::__construct(
            adapter: new LocalFilesystemAdapter(
                $config['root'],
                PortableVisibilityConverter::fromArray($permissionMap, $defaultForDirectories)
            ),
            config: $config,
            pathNormalizer: $pathNormalizer ?? null,
            publicUrlGenerator: new class() implements PublicUrlGenerator {
                public function publicUrl(string $path, Config $config): string
                {
                    return $config->get('public_url') . $path;
                }
            },
            temporaryUrlGenerator: new class() implements TemporaryUrlGenerator {
                public function temporaryUrl(
                    string             $path,
                    DateTimeInterface $expiresAt,
                    Config             $config
                ): string
                {
                    return $config->get('temporary_url') . $path . '?expires_at=' . $expiresAt->format('U');
                }
            }
        );
    }

}