<?php

namespace MkyCore\Console\ApplicationCommands\Schedule;

use Exception;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Console\Scheduler\Schedule;
use MkyCore\Console\Scheduler\Task;

class Run extends \MkyCommand\AbstractCommand
{
    public function __construct(private readonly Schedule $schedule)
    {
        
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     */
    public function execute(Input $input, Output $output): int
    {
        $tasks = $this->schedule->getTasksToDo();
        if (!$tasks) {
            exit('No tasks to perform');
        }
        foreach ($tasks as $signature => $task) {
            $output->info(date('Y-m-d H:i:sP e'), $task->buildCommand());
            $start = microtime(true);
            try {
                $task->run();
                $end = round(microtime(true) - $start, 2);
                $output->success("Task $signature succeeded in $end second" . ($end > 1 ? 's' : ''));
            } catch (Exception $exception) {
                $output->warning("Task $signature failed", $exception->getMessage());
            }
        }
        return self::SUCCESS;
    }
}