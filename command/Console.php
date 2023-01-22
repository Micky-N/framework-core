<?php

namespace MkyCommand;

class Console
{

    /**
     * @var AbstractCommand[]
     */
    private array $commands = [];
    private ?AbstractCommand $currentCommand = null;

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
     * @param array $inputs
     * @return mixed
     * @throws CommandException
     */
    public function execute(array $inputs): mixed
    {
        $input = new Input($inputs);
        $signature = $input->getSignature();
        if ($this->hasCommand($signature)) {
            $this->currentCommand = $this->getCommand($signature);
            return $this->currentCommand->execute($input);
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
     * @return AbstractCommand|null
     */
    public function getCurrentCommand(): ?AbstractCommand
    {
        return $this->currentCommand;
    }
}