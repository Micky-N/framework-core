<?php

namespace MkyCommand;

use MkyCommand\Exceptions\CommandException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Console
{
    
    /**
     * @var AbstractCommand[]
     */
    protected array $commands = [];
    protected ?AbstractCommand $currentCommand = null;
    protected Output $output;

    public function __construct()
    {
        $this->output = new Output;
        $this->addCommand('help', new HelpCommand($this));
    }

    /**
     * @param string $signature
     * @param AbstractCommand $command
     * @return Console
     */
    public function addCommand(string $signature, AbstractCommand $command): static
    {
        $this->commands[$signature] = $command->setSignature($signature);
        return $this;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param Input $input
     * @return mixed
     * @throws CommandException
     */
    public function execute(Input $input): mixed
    {
        $signature = $input->getSignature();
        if ($this->hasCommand($signature)) {
            $this->currentCommand = $this->getCommand($signature);
            if ($this->askHelpCommand($input->options())) {
                $this->currentCommand->setHelpMode();
            }
            $this->currentCommand->settings();
            if ($this->currentCommand->isHelpMode()) {
                return $this->currentCommand->displayHelp($input, $this->output);
            }
            $this->currentCommand->setRealInput($input);
            return $this->currentCommand->execute($input, $this->output);
        }
        throw CommandException::CommandNotFound($signature);
    }

    /**
     * @param string $signature
     * @return bool
     */
    public function hasCommand(string $signature): bool
    {
        return isset($this->commands[$signature]);
    }

    /**
     * @param string $signature
     * @return AbstractCommand
     * @throws CommandException
     */
    public function getCommand(string $signature): AbstractCommand
    {
        return $this->commands[$signature] ?? throw CommandException::CommandNotFound($signature);
    }

    /**
     * @param array $options
     * @return bool
     */
    private function askHelpCommand(array $options): bool
    {
        return isset($options['h']) || isset($options['help']);
    }

    /**
     * @return AbstractCommand|null
     */
    public function getCurrentCommand(): ?AbstractCommand
    {
        return $this->currentCommand;
    }
}