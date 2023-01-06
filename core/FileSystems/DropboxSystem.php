<?php

namespace MkyCore\FileSystems;

use Spatie\Dropbox\Client;
use League\Flysystem\Filesystem;
use Spatie\FlysystemDropbox\DropboxAdapter;

class DropboxSystem extends Filesystem
{
    private string $root = '';
    /**
     * Construction of localFileSystem with filesystem config
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $client = new Client([$config['client_id'], $config['client_secret']]);

        parent::__construct(adapter: new DropboxAdapter($client), config: ['case_sensitive' => false]);
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }
}
