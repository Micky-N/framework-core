<?php

namespace MkyCore\Console\Scheduler;

use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCore\Abstracts\ServiceProvider;
use MkyCore\Application;
use MkyCore\Console\NodeConsoleHandler;
use MkyCore\Container;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Schedule
{

    /**
     * @var array<string, Task>
     */
    private array $tasks = [];

    private ServiceProvider $cliServiceProvider;

    /**
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $application)
    {
        $this->cliServiceProvider = $this->application->get('App\Commands\CliServiceProvider');
        $this->cliServiceProvider->schedule($this);
    }

    /**
     * @param string $signature
     * @return Task
     * @throws CommandException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function task(string $signature): Task
    {
        $tasks = $this->cliServiceProvider->commands;
        $command = $tasks[$signature];
        if(!$command){
            throw CommandException::CommandNotFound($signature);
        }
        $command = $this->application->get($command);
        /** @var AbstractCommand $command */
        return $this->tasks[$signature] = new Task($command->setSignature($signature));
    }

    /**
     * @return array
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return array
     */
    public function getTasksToDo(): array
    {
        return array_filter($this->tasks, fn($task) => $task->toDo());
    }

}