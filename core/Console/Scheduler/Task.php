<?php

namespace MkyCore\Console\Scheduler;

use MkyCommand\AbstractCommand;
use MkyCore\Console\CronInterval\CronExpression;
use MkyCore\Exceptions\Schedule\CronIntervalException;
use Symfony\Component\Process\Process;

class Task
{

    private AbstractCommand $command;

    private string $output = '';

    private CronExpression $interval;

    /**
     * @throws CronIntervalException
     */
    public function __construct(AbstractCommand $command)
    {
        $this->setCommand($command);
        $this->interval('* * * * *');
    }

    /**
     * @throws CronIntervalException
     */
    public function interval(string $interval): static
    {
        $this->interval = new CronExpression($interval);
        return $this;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $process = Process::fromShellCommandline($this->buildCommand(), app()->getBasePath());
        $process->run();
    }

    public function buildCommand(): string
    {
        $command = [];
        $command[] = '"' . PHP_BINARY . '"';
        $command[] = defined('MKY_FILE') ? '"' . MKY_FILE . '"' : '"mky"';
        $command[] = $this->getCommand()->getSignature();
        if ($this->output) {
            $command[] = '--print >>';
            $command[] = $this->output;
        }
        $command[] = '2>&1';
        return join(' ', $command);
    }

    /**
     * @return AbstractCommand
     */
    public function getCommand(): AbstractCommand
    {
        return $this->command;
    }

    /**
     * @param AbstractCommand $command
     */
    public function setCommand(AbstractCommand $command): void
    {
        $this->command = $command;
    }

    public function toDo(): bool
    {
        return $this->interval->isDue();
    }

    /**
     * @param string $output
     * @return Task
     */
    public function output(string $output): Task
    {
        $this->output = $output;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->command->getDescription();
    }

    public function getInterval(): string
    {
        return $this->interval->getInterval();
    }
    
    public function getIntervalExpression(): CronExpression
    {
        return $this->interval;
    }
}