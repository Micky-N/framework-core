<?php

namespace Console;

use Exception;

class Command
{

    const STRUCTURE = [
        'create' => [
            'entity',
            'controller',
            'manager',
            'middleware',
            'provider',
            'module',
            'listener',
            'event',
            'notification',
            'notificationSystem'
        ],
        'show' => [
            'route'
        ]
    ];

    public function __construct()
    {

    }

    /**
     * @param $argv
     * @return mixed
     * @throws Exception
     */
    public function run($argv): mixed
    {
        array_shift($argv);
        $getOpt = [];
        $index = 0;
        foreach ($argv as $arg) {
            $args = explode(':', $arg);
            if (count($args) == 2) {
                $getOpt[$args[0]] = $args[1];
            } elseif (count($args) == 1) {
                $getOpt['arg' . $index] = $args[0];
                $index++;
            }
        }
        $modes = array_keys(self::STRUCTURE);
        $action = '';
        for ($i = 0; $i < count($modes); $i++) {
            $mode = $modes[$i];
            if (in_array($mode, array_keys($getOpt))) {
                $action = $mode;
                break;
            }
        }

        if (!$action) {
            throw new Exception("Action not found");
        }
        if (!in_array($getOpt[$action], self::STRUCTURE[$action])) {
            throw new Exception("Value '{$getOpt[$action]}' not found in action '$action'");
        }
        $class = 'Console\\' . ucfirst($action) . '\\' . ucfirst($getOpt[$action]);
        array_shift($getOpt);
        $instance = new $class($getOpt);
        return $instance->process();
    }
}