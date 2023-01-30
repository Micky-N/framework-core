<?php

namespace MkyCommand;


use MkyCommand\Input;
use MkyCommand\Output;
use MkyCommand\Input\InputOption;

class HelpCommand extends AbstractCommand
{

    protected string $description = 'Display list of commands, the list can be filtered by namespace';

    public function __construct(private readonly Console $console)
    {
    }

    public function settings(): void
    {
        $this->addOption('namespace', 'n', InputOption::OPTIONAL, 'Namespace for filtering commands');
    }

    public function execute(Input $input, Output $output): int
    {
        $section = $output->section();
        $section->text("Help for Mky CLI Command")
            ->newLine(2);
        $this->sendHelp($section, $input, $output);
        $section->read(false);

        return self::SUCCESS;
    }

    private function sendHelp(Section $section, Input $input, Output $output): void
    {
        $namespaces = [];
        $commands = array_values($this->console->getCommands());
        $section->text($output->coloredMessage("Available commands:", 'blue'))->newLine();
        if ($input->option('namespace')) {
            $namespace = $input->option('namespace');
            $commands = array_filter($commands, function ($command) use ($namespace) {
                return str_starts_with($command->getSignature(), "$namespace:");
            });
            $commands = array_values($commands);
        }

        for ($i = 0; $i < count($commands); $i++) {
            $command = $commands[$i];
            $namespace = explode(':', $command->getSignature());
            $namespace = isset($namespace[1]) ? $namespace[0] : false;
            $description = $command->getDescription();
            $namespaces[$namespace ?: count($namespaces)][] = [$output->coloredMessage($command->getSignature(), 'green'), $description];
        }

        ksort($namespaces, SORT_NATURAL);
        foreach ($namespaces as $name => $commandNames) {
            $this->helpByNamespace($name, $commandNames, $section, $output);
        }
    }

    private function helpByNamespace(string $name, mixed $commandNames, Section $section, Output $output): void
    {
        if (!is_numeric($name)) {
            $section->text(" " . $output->coloredMessage($name, 'yellow'))->newLine();
        }
        $table = $output->table();
        for ($i = 0; $i < count($commandNames); $i++) {
            $commandName = $commandNames[$i];
            $table->addRow($commandName);
        }
        $section->text($table->setIndent(1)
            ->hideBorder()
            ->getTable());
    }
}
