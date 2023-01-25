<?php

namespace MkyCommand;

use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Console
{

    use Color;

    /**
     * @var AbstractCommand[]
     */
    private array $commands = [];
    private ?AbstractCommand $currentCommand = null;
    private readonly ?ContainerInterface $container;

    /**
     * @param ?ContainerInterface $container
     * @throws ConsoleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->addCommand('help', new HelpCommand($this));
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
        $signature = $input->getSignature();
        if ($this->hasCommand($signature)) {
            $this->currentCommand = $this->getCommand($signature);
            if ($this->askHelpCommand($input->getOptions())) {
                $this->currentCommand->setHelpMode();
            }
            $this->currentCommand->settings();
            if ($this->currentCommand->isHelpMode()) {
                return $this->currentCommand->displayHelp($input);
            }
            $this->currentCommand->setRealInput($input);
            return $this->currentCommand->execute();       
            
        }
        throw CommandException::CommandNotFound($signature, $this->coloredMessage('php mky help', 'yellow'));
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