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
            exit($this->helpAll($input));
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

    private function helpAll(Input $input): string
    {
        $res = [];
        $namespaces = [];
        $commands = array_values($this->commands);
        $res[] = "Help for Mky Command CLI" . "\n\n";
        $res[] = $this->coloredMessage("Available commands:", 'blue') . "\n";
        if (!$input->hasOption('n') && !$input->hasOption('namespace')) {
            $table = new ConsoleTable();
            $table->addRow([$this->coloredMessage('--help, -h [-n|--namespace]', 'green'), 'Display help for a command or all commands, can be filtered by namespace']);
            $res[] = $table->setIndent(1)
                ->hideBorder()
                ->getTable();
        } else {
            $namespace = '';
            if ($input->hasOption('n')) {
                $namespace = $input->getOption('n');
            } else if ($input->hasOption('namespace')) {
                $namespace = $input->getOption('namespace');
            }
            $commands = array_filter($commands, function ($command) use ($namespace) {
                return str_starts_with($command->getSignature(), "$namespace:");
            });
            $commands = array_values($commands);
        }
        for ($i = 0; $i < count($commands); $i++) {
            $command = $commands[$i];
            $namespace = explode(':', $command->getSignature());
            $namespace = $namespace[0];
            $description = $command->getDescription();
            $namespaces[$namespace][] = [$this->coloredMessage($command->getSignature(), 'green'), $description];
        }

        foreach ($namespaces as $name => $commandNames) {
            $this->helpByNamespace($name, $commandNames, $res);
        }

        return join('', $res);
    }

    private function helpByNamespace(string $name, mixed $commandNames, array &$res = []): void
    {
        $res[] = " " . $this->coloredMessage($name, 'yellow') . "\n";
        $table = new ConsoleTable();
        for ($i = 0; $i < count($commandNames); $i++) {
            $commandName = $commandNames[$i];
            $table->addRow($commandName);
        }
        $res[] = $table->setIndent(1)
            ->hideBorder()
            ->getTable();
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
        $res[] = $this->coloredMessage('Command: php ' . $file, 'gray').' '. $this->coloredMessage($command->getSignature(), 'light_yellow') . "\n";
        $res[] = $this->coloredMessage('Description:', 'blue');
        $res[] = "  " . $command->getDescription() . "\n";
        $res[] = $this->coloredMessage('Arguments:', 'yellow');
        $table = new ConsoleTable();
        for ($i = 0; $i < count($arguments); $i++) {
            $argument = $arguments[$i];
            $table->addRow([$this->getInputType($argument), $argument->getDescription(), '']);
        }

        for ($j = 0; $j < count($options); $j++) {
            $option = $options[$j];
            $default = '';
            if ($option->hasDefault()) {
                $default = $option->getDefault();
                $default = is_array($default) ? '[' . join(', ', $default) . ']' : $default;
            }
            $table->addRow([$this->getInputType($option), $option->getDescription(), $default]);
        }

        $res[] = $table->setIndent(1)
            ->hideBorder()
            ->getTable();

        return join("\n", $res);
    }

    private function getInputType(InputArgument|InputOption $input): string
    {
        $type = $input instanceof InputOption ? $this->getOptionType($input) : $this->getArgumentType($input);
        $text = '';
        if($input instanceof InputOption){
            $text .= $input->hasShortName() ? '-'.$input->getShortname().'|' : '';
        }
        $text .= $input->getName() . " [$type]";
        return $this->coloredMessage($text, 'green');
    }

    private function getOptionType(InputOption $option): string
    {
        $type = '';
        $optionType = $option->getType();
        if ($optionType === InputOption::REQUIRED) {
            $type = 'required';
        } else if ($optionType === (InputOption::ARRAY | InputOption::REQUIRED)) {
            $type = 'array|required';
        } else if ($optionType === InputOption::OPTIONAL) {
            $type = 'optional';
        } else if ($optionType === (InputOption::ARRAY | InputOption::OPTIONAL)) {
            $type = 'array|optional';
        } else if ($optionType === InputOption::NONE) {
            $type = 'none_value';
        } else if ($optionType === InputOption::NEGATIVE) {
            $type = 'negative_value';
        }
        return $type;
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
        }

        return $type;
    }

    /**
     * @return AbstractCommand|null
     */
    public function getCurrentCommand(): ?AbstractCommand
    {
        return $this->currentCommand;
    }
}