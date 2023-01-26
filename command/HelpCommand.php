<?php

namespace MkyCommand;


use MkyCommand\Input\InputOption;

class HelpCommand extends AbstractCommand
{

    protected string $description = 'Display list of commands, the list can be filtered by namespace';

    public function __construct(private readonly Console $console)
    {
        parent::__construct();
    }

    public function settings(): void
    {
        $this->addOption('namespace', 'n', InputOption::OPTIONAL, 'Namespace for filtering commands');
    }

    public function execute(): int
    {
        $section = $this->output->section();
        $section->text("Help for Mky CLI Command")
            ->newLine(2);
        $this->sendHelp($section);
        $section->read(false);

        return self::SUCCESS;
    }

    private function sendHelp(Section $section): void
    {
        $namespaces = [];
        $commands = array_values($this->console->getCommands());
        $section->text($this->output->coloredMessage("Available commands:", 'blue'))->newLine();
        if ($this->input->hasOption('namespace') && $this->input->getOption('namespace')) {
            $namespace = $this->input->getOption('namespace');
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
            $namespaces[$namespace ?: count($namespaces)][] = [$this->output->coloredMessage($command->getSignature(), 'green'), $description];
        }

        ksort($namespaces, SORT_NATURAL);
        foreach ($namespaces as $name => $commandNames) {
            $this->helpByNamespace($name, $commandNames, $section);
        }
    }

    private function helpByNamespace(string $name, mixed $commandNames, Section $section): void
    {
        if (!is_numeric($name)) {
            $section->text(" " . $this->output->coloredMessage($name, 'yellow'))->newLine();
        }
        $table = $this->output->table();
        for ($i = 0; $i < count($commandNames); $i++) {
            $commandName = $commandNames[$i];
            $table->addRow($commandName);
        }
        $section->text($table->setIndent(1)
            ->hideBorder()
            ->getTable());
    }
}
