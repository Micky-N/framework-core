<?php

namespace MkyCommand;

use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;
use MkyCore\Console\Show\ConsoleTable;
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
            exit($this->helpAll());
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
            exit($this->helpCommand($this->currentCommand, $input->getFile()));
        }
        throw CommandException::CommandNotFound($signature);
    }

    private function askHelp(string $signature): bool
    {
        return in_array($signature, ['-h', '--help']);
    }

    private function helpAll(): string
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

    private function helpCommand(AbstractCommand $command, string $file): string
    {
        $arguments = $command->getArguments() ? array_values($command->getArguments()) : [];
        $options = $command->getOptions() ? array_values($command->getOptions()) : [];
        $res = [];
        $res[] = "Help for Mky Command CLI";
        $res[] = $this->coloredMessage('Current command: php ' . $file . ' ' . $command->getSignature(), 'gray') . "\n";
        $res[] = $this->coloredMessage($command->getSignature(), 'yellow') . ": {$command->getDescription()}";
        $table = new ConsoleTable();
        for ($i = 0; $i < count($arguments); $i++) {
            $argument = $arguments[$i];
            $table->addRow([$this->getInputType($argument), $argument->getDescription()]);
        }

        for ($j = 0; $j < count($options); $j++) {
            $option = $options[$j];
            $table->addRow([$this->getInputType($option), $option->getDescription()]);
        }

        $table->setIndent(1)
            ->hideBorder();
        $res[] = $table->getTable();
        return join("\n", $res);
    }

    private function getInputType(InputArgument|InputOption $input): string
    {
        $type = $input instanceof InputOption ? $this->getOptionType($input) : $this->getArgumentType($input);
        $text = $input->getName() . " [$type]";
        return $this->coloredMessage($text, 'green');
    }

    private function getOptionType(InputOption $option): string
    {
        return 'opt';
    }

    private function getArgumentType(InputArgument $argument): string
    {
        $type = '';
        $argumentType = $argument->getType();
        if ($argumentType === InputArgument::REQUIRED) {
            $type = 'required';
        } else if ($argumentType === (InputArgument::ARRAY | InputArgument::REQUIRED)) {
            $type = 'array|required';
        } else if ($argumentType === InputArgument::OPTIONAL) {
            $type = 'optional';
        } else if ($argumentType === (InputArgument::ARRAY | InputArgument::OPTIONAL)) {
            $type = 'array|optional';
        } else if ($argumentType === InputArgument::ARRAY) {
            $type = 'array';
        }
        return $type;
    }

    public function help(): bool
    {
        $currentCommand = self::$currentCommand;
        $selfHelp = $this->getHelps();
        echo "Help for Mky Command CLI\n";
        if ($currentCommand) {
            echo $this->coloredMessage("Current command: php mky $currentCommand", 'gray') . "\n";
        }
        foreach ($selfHelp as $method => $helps) {
            echo "\n" . $this->coloredMessage($method, 'yellow') . ":\n";
            $table = new ConsoleTable();
            foreach ($helps as $key => $help) {
                $help = (array)$help;
                $description = $help[0];
                $params = $help[1] ?? null;
                $table->addRow([$this->formatKey($key, $params), $description]);
            }
            $table->setIndent(2)
                ->hideBorder()
                ->display();
        }
        return true;
    }
}