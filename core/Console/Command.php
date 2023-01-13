<?php

namespace MkyCore\Console;

use Exception;
use MkyCore\Application;
use MkyCore\Console\Show\ConsoleTable;

class Command
{

    use Color;
    
    const HELPS = [
        'create' => [
            'module' => ['Create module structure, --crud to implement crud methods to the controller', '--crud, --crud-api'],
            'entity' => 'Create entity class',
            'controller' => ['Create controller class, the name is suffixed by \'Controller\', --crud to implement crud methods', '--crud, --crud-api'],
            'manager' => 'Create manager class, the name is suffixed by \'Manager\'',
            'middleware' => 'Create middleware class, the name is suffixed by \'Middleware\'',
            'provider' => 'Create provider class, the name is suffixed by \'ServiceProvider\'',
            'listener' => 'Create listener class, the name is suffixed by \'Listener\'',
            'event' => 'Create event class, the name is suffixed by \'Event\'',
        ],
        'migration' => [
            'create' => 'Create migration file in format createUserTable => 123456_create_user_table.php',
            'run' => ['Migrate database table, -f to specify the file number, --pop to populate database', '-f --pop'],
            'clear' => 'clear database',
            'reset' => ['clear and migrate database', '--pop']
        ],
        'populator' => [
            'create' => 'Create populator class, the name is suffixed by \'Populator\'',
            'run' => ['Populate database, -f to specify class', '-f']
        ],
        'install' => [
            'jwt' => 'Implement json web token migration and config file',
            'remember' => 'Implement the remember authenticate system',
        ],
        'show' => [
            'route' => ['Show list of routes, can be filtered by controller, request methods, name (regex) or/and url (regex)', '--filter'],
            'module' => 'Show list of modules'
        ],
        'tmp' => [
            'link' => 'Create symlinks from filesystems config'
        ],
        'generate' => [
            'key' => 'Generate a new app key'
        ]
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
        ],
        'show' => [
            'route',
            'module'
        ],
        'migration' => [
            'create',
            'run',
            'rollback',
            'reset'
        ],
        'populator' => [
            'create',
            'run'
        ],
        'install' => [
            'jwt',
            'remember',
        ],
        'tmp' => [
            'link'
        ],
        'generate' => [
            'key'
        ]
    ];
    public static string $currentCommand = '';

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
        if(!$argv){
            return null;
        }
        $getOpt = [];
        $index = 0;
        self::$currentCommand = $argv[0] !== '-h' && $argv[0] !== '--help' ? $argv[0] : '';
        if (in_array('-h', $argv) || in_array('--help', $argv)) {
            return $this->help();
        }
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
            throw new Exception("Value '$getOpt[$action]' not found in action '$action'");
        }
        $class = 'MkyCore\Console\\' . ucfirst($action) . '\\' . ucfirst($getOpt[$action]);
        array_shift($getOpt);
        $instance = new $class($this->app, $getOpt);
        return $instance->process();
    }

    public function help(): bool
    {
        $currentCommand = self::$currentCommand;
        $selfHelp = $this->getHelps();
        echo "Help for Mky Command CLI\n";
        if($currentCommand){
            echo $this->getColoredString("Current command: php mky $currentCommand", 'gray')."\n";
        }
        foreach ($selfHelp as $method => $helps) {
            echo "\n" . $this->getColoredString($method, 'yellow') . ":\n";
            $table = new ConsoleTable();
            foreach ($helps as $key => $help) {
                $help = (array) $help;
                $description = $help[0];
                $params = $help[1] ?? null;
                $table->addRow([$this->formatKey($key, $params), $description]);
            }
            $table->setIndent(2)
                ->hideBorder()
                ->display();
        }
        return true;
    }
    
    public function getHelps(): array
    {
        $selfHelps = self::HELPS;
        $currentCommand = self::$currentCommand;
        if(!$currentCommand){
            return $selfHelps;
        }
        $commands = explode(':', $currentCommand);
        $res = [];
        if(isset($commands[1])){
            $res[$commands[0]][$commands[1]] = $selfHelps[$commands[0]][$commands[1]];
        }else{
            $res[$commands[0]] = $selfHelps[$commands[0]];
        }
        return $res;
    }

    public function formatKey(string $key, string $params = null): string
    {
        if($params){
            $key .= " [$params]";
        }
        return $this->getColoredString($key, 'green');
    }
}