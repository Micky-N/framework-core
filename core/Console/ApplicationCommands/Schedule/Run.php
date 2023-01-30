<?php

namespace MkyCore\Console\ApplicationCommands\Schedule;

use Exception;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Console\Scheduler\Schedule;

class Run extends \MkyCommand\AbstractCommand
{
    public function __construct(private readonly Schedule $schedule)
    {
    }

    public function execute(Input $input, Output $output): mixed
    {
        $tasks = $this->schedule->getTasks();
        if (!$tasks) {
            $output->info('');
        }
        foreach ($tasks as $signature => $task) {
            if ($task->toDo()) {
                $this->displayTask($output, $signature);
                $start = microtime(true);
                try {
                    $task->run();
                    $end = round(microtime(true) - $start, 2);
                    $output->success("Task $signature succeeded in $end second" . ($end > 1 ? 's' : ''));
                } catch (Exception $exception) {
                    $output->warning("Task $signature failed", $exception->getMessage());
                }
            }
        }
        return self::SUCCESS;
    }

    /**
     * @param Output $output
     * @param int|string $signature
     * @return void
     */
    private function displayTask(Output $output, int|string $signature): void
    {
        $output->info(date('Y-m-d H:i:sP e'), sprintf('%s %s %s', PHP_BINARY, defined('MKY_FILE') ? MKY_FILE : 'mky', $signature));
        $output->breakLine();
    }
}