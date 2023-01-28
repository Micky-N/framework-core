<?php

namespace MkyCore\Console\Tmp;

use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\FileManager;
use ReflectionException;

class Link extends AbstractCommand
{

    protected string $description = '';

    public function __construct(private readonly Application $application)
    {
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): int
    {
        $links = config('filesystems.links');
        foreach ($links as $link => $target) {
            $link = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $link);
            $target = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $target);
            if (file_exists($link)) {
                $output->error('File already exists');
                continue;
            }
            if (is_link($link)) {
                unlink($link);
            }

            if ($this->application->get(FileManager::class)->get('local')->link($target, $link)) {
                echo $output->coloredMessage('Symlink successfully created', 'yellow') . ': ' . "$link >> $target" . "\n";
            }
        }
        $output->success('Symbolic link successfully created');
        return self::SUCCESS;
    }
}