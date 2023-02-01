<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Event extends Create
{

    protected string $outputDirectory = 'Events';
    protected string $createType = 'event';
    protected string $suffix = 'Event';

    protected string $description = 'Create a new event';

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of event, by default is suffixed by Event')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the event');
    }

    /**
     * @param string $module
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws CommandException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function createEventServiceProvider(string $module, Input $input, Output $output): void
    {
        $provider = new Provider($this->application, ['name' => 'event', 'module' => $module]);
        if ($provider->execute($input, $output)) {
            $message = 'Provider created';
            $res = $module . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . 'EventServiceProvider.php';
            echo "\n";
            $output->success($message, $res);
        }
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws ReflectionException
     * @throws CommandException
     * @throws NotInstantiableContainerException
     */
    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): void
    {
        $namespace = $vars['module'];
        $eventServiceProvider = $namespace . '\Providers\EventServiceProvider';
        if (!class_exists($eventServiceProvider)) {
            if ($input->confirm('Do you want to create the EventServiceProvider?', true)) {
                $this->createEventServiceProvider($namespace, $input, $output);
            }
        }
    }
}