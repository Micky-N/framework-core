<?php

namespace MkyCore\Console\ApplicationCommands\Show;

use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Console\Show\ConsoleTable;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Module extends AbstractCommand
{

    const HEADERS = [
        'getAlias' => 'Alias',
        'getModuleKernel' => 'Kernel',
    ];


    protected string $description = 'Show all modules';

    public function __construct(private readonly Application $application)
    {
    }

    public function settings(): void
    {
        $this->addOption('print', 'p', Input\InputOption::NONE, 'Display modules in print mode');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws CommandException
     */
    public function execute(Input $input, Output $output): int
    {
        $table = $output->table();
        $table->setHeaders(array_map(fn($header) => $output->coloredMessage($header, 'green'), array_values(self::HEADERS)));
        $modules = array_keys($this->application->getModules());
        $headers = array_keys(self::HEADERS);
        for ($i = 0; $i < count($modules); $i++) {
            $module = $this->application->getModuleKernel($modules[$i]);
            $array = [];
            foreach ($headers as $header) {
                if ($header == 'getModuleKernel') {
                    $array[] = get_class($module);
                } else {
                    $array[] = $module->{$header}();
                }
            }
            $table->addRow($array);
        }
        if ($input->option('print')) {
            echo "List of modules:\n";
        }

        $table->setPadding(2)
            ->setIndent(2)
            ->showAllBorders()
            ->display();
        return self::SUCCESS;
    }

}