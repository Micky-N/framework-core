<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Application;

class Command extends Create
{
    protected string $outputDirectory = 'Commands';
    protected string $createType = 'command';
    protected string $suffix = 'Command';

    protected string $description = 'Create a new command';

    public function __construct(Application $application, array $variables = [])
    {
        $variables['module'] = 'root';
        parent::__construct($application, $variables);
    }

    public function settings(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the command, by default is suffixed by Command')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the command');
    }

    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars)
    {

    }
}