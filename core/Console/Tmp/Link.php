<?php

namespace MkyCore\Console\Tmp;

use MkyCore\Console\Create\Create;
use MkyCore\FileManager;

class Link extends Create
{
    public function process(): bool|string
    {
        $links = config('filesystems.links');
        foreach ($links as $link => $target) {
            $link = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $link);
            $target = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $target);
            if (file_exists($link)) {
                echo $this->coloredMessage('File already exists', 'red', 'bold') . "\n";
                continue;
            }
            if (is_link($link)) {
                unlink($link);
            }

            if ($this->app->get(FileManager::class)->get('local')->link($target, $link)) {
                echo $this->coloredMessage('Symlink successfully created', 'yellow') . ': ' . "$link >> $target" . "\n";
            }
        }
        return $this->success('Symbolic link successfully created');
    }
}