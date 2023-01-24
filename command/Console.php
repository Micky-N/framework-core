<?php

namespace MkyCommand;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Console
{

    /**
     * @var AbstractCommand[]
     */
    private array $commands = [];
    private ?AbstractCommand $currentCommand = null;
    private readonly ?ContainerInterface $container;
    private bool $helpMode = false;

    /**
     * @param ?ContainerInterface $container
     * @return void
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
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
     * @param array $inputs
     * @return mixed
     * @throws CommandException
     */
    public function execute(array $inputs): mixed
    {
        $input = new Input($inputs);
        if ($this->askHelp($input->getSignature())) {
            exit($this->help());
        }
        $signature = $input->getSignature();
        if ($this->hasCommand($signature)) {
            $this->currentCommand = $this->getCommand($signature);
            if ($this->askHelpCommand($input->getOptions())) {
                $this->currentCommand->setHelpMode();
            }
            $this->currentCommand->settings();
            if (!$this->currentCommand->isHelpMode()) {
                $this->currentCommand->setRealInput($input);
                return $this->currentCommand->execute();
            }
            dd($this->currentCommand);
            exit($this->help($this->currentCommand));
        }
        throw CommandException::CommandNotFound($signature);
    }

    private function askHelp(string $signature): bool
    {
        return in_array($signature, ['-h', '--help']);
    }

    private function help(?AbstractCommand $command = null): string
    {
        dd($this->commands);
        $help = ['test'];
        return join($help);
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