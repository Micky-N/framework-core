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

    /**
     * @param ?ContainerInterface $container
     */
    public function __construct(protected readonly ?ContainerInterface $container = null)
    {
        $this->output = new Output;
    }

    /**
     * @param string $signature
     * @param string|AbstractCommand $command
     * @return Console
     * @throws ConsoleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addCommand(string $signature, string|AbstractCommand $command): static
    {
        if (is_string($command)) {
            if (!class_exists($command)) {
                throw ConsoleException::CommandNotFound($command);
            }
            $command = $this->instantiateCommand($command);
        } else {
            if (!($command instanceof AbstractCommand)) {
                throw ConsoleException::CommandNotExtendsAbstract(get_class($command));
            }
        }
        $this->commands[$signature] = $command->setSignature($signature);
        return $this;
    }

    /**
     * @param string $command
     * @return AbstractCommand
     * @throws ConsoleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function instantiateCommand(string $command): AbstractCommand
    {
        if ($this->container) {
            $commandInstance = $this->container->get($command);
        } else {
            $commandInstance = new $command();
        }
        if (!($commandInstance instanceof AbstractCommand)) {
            throw ConsoleException::CommandNotExtendsAbstract($command);
        }
        return $commandInstance;
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