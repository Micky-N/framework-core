<?php

namespace MkyCore\Console\Migration;

use MkyCommand\AbstractCommand;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Migration\DB;

abstract class Migration extends AbstractCommand
{
    public static bool $query = false;
    protected DB $migrationDB;

    public function __construct(protected readonly Application $application)
    {
        $this->migrationDB = $this->application->get(DB::class);
    }

    protected function sendResponse(Output $output, array $success, array $errors)
    {
        if ($success) {
            for ($i = 0; $i < count($success); $i++) {
                $response = $success[$i];
                $output->coloredMessage($response[0], 'green', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
            }
        }
        if ($errors) {
            for ($i = 0; $i < count($errors); $i++) {
                $response = $errors[$i];
                $output->coloredMessage($response[0], 'red', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
            }
        }
    }
}