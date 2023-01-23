<?php

namespace MkyCore\Console\Migration;

use MkyCore\Application;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\Migration\DB;

abstract class Migration extends AbstractCreate
{
    public static bool $query = false;
    protected DB $migrationDB;

    public function __construct(Application $app, array $params = [], array $moduleOptions = [])
    {
        $this->migrationDB = $app->get(DB::class);
        parent::__construct($app, $params, $moduleOptions);
    }

    protected function sendResponse(array $success, array $errors)
    {
        if ($success) {
            for ($i = 0; $i < count($success); $i++) {
                $response = $success[$i];
                echo $this->coloredMessage($response[0], 'green', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
            }
        }
        if ($errors) {
            for ($i = 0; $i < count($errors); $i++) {
                $response = $errors[$i];
                echo $this->coloredMessage($response[0], 'red', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
            }
        }
    }
}