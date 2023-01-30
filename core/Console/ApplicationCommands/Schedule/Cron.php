<?php

namespace MkyCore\Console\ApplicationCommands\Schedule;

use Carbon\Carbon;
use MkyCommand\Input;
use MkyCommand\Output;
use Symfony\Component\Process\Process;

class Cron extends \MkyCommand\AbstractCommand
{

    public function execute(Input $input, Output $output): mixed
    {
        $output->info('Schedule cron started successfully.');

        [$lastTaskStartedAt, $keyOfLastTaskWithOutput, $tasks] = [null, null, []];

        while (true) {
            usleep(100 * 1000);

            if (Carbon::now()->second === 0 &&
                !Carbon::now()->startOfMinute()->equalTo($lastTaskStartedAt)) {
                $tasks[] = $task = new Process([
                    PHP_BINARY,
                    defined('MKY_FILE') ? MKY_FILE : 'mky',
                    'schedule:run',
                ]);

                $task->start();

                $lastTaskStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($tasks as $key => $task) {
                $outputTask = trim($task->getIncrementalOutput()) .
                    trim($task->getIncrementalErrorOutput());

                if (!empty($outputTask)) {
                    if ($key !== $keyOfLastTaskWithOutput) {
                        $output->info(PHP_EOL . date('Y-m-d H:i:sP e') . ' Task #' . ($key + 1) . ' output', $outputTask);

                        $keyOfLastTaskWithOutput = $key;
                    }
                }

                if (!$task->isRunning()) {
                    unset($tasks[$key]);
                }
            }
        }
    }
}