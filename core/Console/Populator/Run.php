<?php

namespace MkyCore\Console\Populator;

use Exception;
use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Abstracts\Populator;

class Run extends AbstractCommand
{
    public static int $count = 0;

    public function execute(Input $input, Output $output): bool|string
    {
        $params = $this->parseParams();
        try {
            $file = $params['-f'] ?? 'runner';
            $file = $this->handlerRules('file', $file);
            $populator = "Database\Populators\\$file";
            if(!class_exists($populator)){
                return $this->error("Populator class not found", $populator);
            }
            $populator = new $populator();
            if(!($populator instanceof Populator)){
                return $this->error("Populator class must extends from MkyCore\Abstracts\Populator", $populator);
            }
            $populator->populate();
            $count = self::$count;
            echo $this->coloredMessage('Database successfully populated', 'green', 'bold');
            echo ': '.$count.' record'.($count > 1 ? 's' : '');
            return true;
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}