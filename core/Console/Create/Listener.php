<?php

namespace MkyCore\Console\Create;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;

class Listener extends Create
{
    protected string $outputDirectory = 'Listeners';
    protected string $createType = 'listener';
    protected string $suffix = 'Listener';

    protected string $description = 'Create a new listener';

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of listener, by default is suffixed by Listener')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the listener');
    }

    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): void
    {
    }
}