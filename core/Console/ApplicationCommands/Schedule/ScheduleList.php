<?php

namespace MkyCore\Console\ApplicationCommands\Schedule;

use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Console\Scheduler\Schedule;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class ScheduleList extends AbstractCommand
{

    const HEADERS = [
        'buildCommand' => 'Command',
        'getInterval' => 'Interval',
        'getDescription' => 'Description',
    ];


    protected string $description = 'Show all scheduled tasks';

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
        $headers = array_keys(self::HEADERS);
        $schedule = new Schedule($this->application);
        $tasks = array_values($schedule->getTasks());
        ksort($tasks, SORT_NATURAL);
        for ($i = 0; $i < count($tasks); $i++) {
            $task = $tasks[$i];
            $array = [];
            foreach ($headers as $header) {
                $array[] = $task->{$header}();
            }
            $table->addRow($array);
        }
        if ($input->option('print')) {
            echo "List of modules:\n";
        }

        $table->display();
        return self::SUCCESS;
    }

}