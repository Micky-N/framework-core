<?php

namespace MkyCommand;


use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;

class HelpCommand extends AbstractCommand
{
    use Color;

    protected string $description = 'Display list of commands, the list can be filtered by namespace';

    public function settings(): void
    {
        $this->addOption('namespace', 'n', InputOption::OPTIONAL, 'Namespace for filtering commands');
    }

    public function __construct(private readonly Console $console)
    {
        parent::__construct();
    }

    public function execute(): mixed
    {
        $res = [];
        $res[] = "Help for Mky Command CLI" . "\n\n";
        $namespaces = [];
        $commands = array_values($this->console->getCommands());
        $res[] = $this->coloredMessage("Available commands:", 'blue') . "\n";
        if ($this->input->hasOption('namespace') && $this->input->getOption('namespace')) {
            $namespace = $this->input->getOption('namespace');
            $commands = array_filter($commands, function ($command) use ($namespace) {
                return str_starts_with($command->getSignature(), "$namespace:");
            });
            $commands = array_values($commands);
        }
        exit($this->sendHelp($res, $namespaces, $commands));
    }

    private function sendHelp($res, $namespaces, $commands): string
    {
        for ($i = 0; $i < count($commands); $i++) {
            $command = $commands[$i];
            $namespace = explode(':', $command->getSignature());
            $namespace = isset($namespace[1]) ? $namespace[0] : false;
            $description = $command->getDescription();
            $namespaces[$namespace ?: count($namespaces)][] = [$this->coloredMessage($command->getSignature(), 'green'), $description];
        }

        ksort($namespaces, SORT_NATURAL);
        foreach ($namespaces as $name => $commandNames) {
            $this->helpByNamespace($name, $commandNames, $res);
        }

        return join('', $res);
    }

    private function helpByNamespace(string $name, mixed $commandNames, array &$res = []): void
    {
        if(!is_numeric($name)){
            $res[] = " " . $this->coloredMessage($name, 'yellow') . "\n";
        }
        $table = new ConsoleTable();
        for ($i = 0; $i < count($commandNames); $i++) {
            $commandName = $commandNames[$i];
            $table->addRow($commandName);
        }
        $res[] = $table->setIndent(1)
            ->hideBorder()
            ->getTable();
    }

    public function helpCommand(AbstractCommand $command, string $file): string
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
}