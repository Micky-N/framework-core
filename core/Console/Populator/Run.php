<?php

namespace MkyCore\Console\Populator;

use Exception;
use MkyCore\Abstracts\Populator;

class Run extends Populate
{
    public static int $count = 0;

    protected array $rules = [
        'file' => ['ucfirst', 'ends:populator']
    ];

    public function process(): bool|string
    {
        $params = $this->params;
        try {
            $file = isset($params['arg0']) && !str_starts_with($params['arg0'], '--') ? $params['arg0'] : 'runner';
            $file = $this->handlerRules('file', $file);
            $populator = "Database\Populators\\$file";
            if(!class_exists($populator)){
                return $this->sendError("Populator class not found", $populator);
            }
            $populator = new $populator();
            if(!($populator instanceof Populator)){
                return $this->sendError("Populator class must extends from MkyCore\Abstracts\Populator", $populator);
            }
            $populator->populate();
            $count = self::$count;
            echo $this->getColoredString('Database successfully populated', 'green', 'bold');
            echo ': '.$count.' record'.($count > 1 ? 's' : '');
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}