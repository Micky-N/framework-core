<?php

namespace MkyCore\Console\Scheduler;

use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Schedule
{

    /**
     * @var array<string, Task>
     */
    private array $tasks = [];

    public function __construct(private readonly Application $application)
    {

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
        $command = $this->application->getCommand($signature);
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

}