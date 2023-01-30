<?php

namespace MkyCore\Console\Scheduler;

use MkyCommand\AbstractCommand;
use Symfony\Component\Process\Process;

class Task
{

    private AbstractCommand $command;

    private string $output = '';

    public function __construct(AbstractCommand $command)
    {
        $this->setCommand($command);
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

    /**
     * @return void
     */
    public function run(): void
    {
        Process::fromShellCommandline($this->buildCommand(), app()->getBasePath())->run();
    }

    public function toDo(): bool
    {
        return true;
    }

    private function buildCommand(): string
    {
        $command = [];
        $command[] = '"'.PHP_BINARY.'"';
        $command[] = defined('MKY_FILE') ? '"'.MKY_FILE.'"' : '"mky"';
        $command[] = $this->getCommand()->getSignature();
        if($this->output){
            $command[] = '>>';
            $command[] = $this->output;
        }
        $command[] = '2>&1';
        return join(' ', $command);
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
}