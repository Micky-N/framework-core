<?php

namespace MkyCore\Console;

use Exception;
use MkyCore\Application;

class Command
{
    const HELPS = [
        'create' => [
            'module:--crud(-api)' => 'Create module structure',
            'entity' => 'Create entity class',
            'controller:--crud(-api)' => 'Create controller class, the name is suffix by \'Controller\'',
            'manager' => 'Create manager class, the name is suffix by \'Manager\'',
            'middleware' => 'Create middleware class, the name is suffix by \'Middleware\'',
            'provider' => 'Create provider class, the name is suffix by \'ServiceProvider\'',
            'listener' => 'Create listener class, the name is suffix by \'Listener\'',
            'event' => 'Create event class, the name is suffix by \'Event\'',
            'notification' => 'Create notification class, the name is suffix by \'Notification\'',
            'notificationSystem' => 'Create notification system class, the name is suffix by \'NotificationSystem\''
        ],
        'migration' => [
            'create' => 'Create migration file in format createUserTable => 123456_create_user_table.php',
            'run:-f, --pop' => 'Migrate database table, -f to specify the file number, --pop to populate database',
            'clear' => 'clear database',
            'reset:--pop' => 'clear and migrate database'
        ],
        'populator' => [
            'create' => 'Create populator class, the name is suffix by \'Populator\'',
            'run:-f' => 'Populate database, -f to specify class'
        ],
        'install' => [
            'jwt' => 'Implement jwt migration and config file'
        ],
        'show' => [
            'route:--filterName' => 'Show list of routes, can be filtered by controller, request methods, name (regex) or/and url (regex)',
            'module' => 'Show list of modules'
        ],
    ];
    
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
            'route',
            'module'
        ],
        'migration' => [
            'create',
            'run',
            'clear',
            'reset'
        ],
        'populator' => [
            'create',
            'run'
        ],
        'install' => [
            'jwt'
        ]
    ];

    public function __construct(private readonly Application $app)
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
        if(isset($argv[0]) && in_array($argv[0], ['--help', '-h'])){
            return $this->help();
        }
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
        $class = 'MkyCore\Console\\' . ucfirst($action) . '\\' . ucfirst($getOpt[$action]);
        array_shift($getOpt);
        $instance = new $class($this->app, $getOpt);
        return $instance->process();
    }

    public function help(): bool
    {
        // TODO implement help
        return true;
    }
}