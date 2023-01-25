<?php

namespace MkyCommand;


use MkyCommand\Input\InputOption;

class HelpCommand extends AbstractCommand
{

    protected string $description = 'Display list of commands, the list can be filtered by namespace';

    public function settings(): void
    {
        $this->addOption('namespace', 'n', InputOption::OPTIONAL, 'Namespace for filtering commands');
    }

    public function __construct(private readonly Console $console)
    {
        parent::__construct();
    }

    public function execute(): string
    {
        $res = [];
        $res[] = "Help for Mky CLI Command" . "\n\n";
        $this->sendHelp($res);
        return join("", $res);
    }

    private function sendHelp(array &$res): void
    {
        $namespaces = [];
        $commands = array_values($this->console->getCommands());
        $res[] = $this->$this->output->coloredMessage("Available commands:", 'blue') . "\n";
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
            $this->helpByNamespace($name, $commandNames, $res);
        }
    }

    private function helpByNamespace(string $name, mixed $commandNames, array &$res = []): void
    {
        if (!is_numeric($name)) {
            $res[] = " " . $this->output->coloredMessage($name, 'yellow') . "\n";
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
}
