<?php

namespace MkyCore\Console\Create;

use MkyCore\Facades\DB;

class Module extends Create
{

    const DEFAULT_DIRS = [
        'Controllers',
        'Middlewares',
        'Providers',
        'Entities',
        'Managers'
    ];

    const CLASS_FILE = [
        'Controllers' => 'Controller',
        'Middlewares' => 'Middleware',
        'Providers' => 'Provider',
        'Entities' => 'Entity',
        'Managers' => 'Manager'
    ];

    const DEFAULT_FILES = [
        'aliases' => 'Middlewares/aliases',
    ];


    public function process(): bool
    {
        $params = $this->params;
        $name = array_shift($params);
        if (!$name) {
            return $this->sendError("No name entered", 'NULL');
        }
        $module = ucfirst($name) . 'Module';
        if (is_dir("app/$module")) {
            return $this->sendError("Module already exists", $module);
        }
        $getTables = array_map(function ($tables) {
            return $tables['Tables_in_' . DB::getDatabase()];
        }, DB::query('SHOW TABLES'));
        do {
            $confirm = true;
            $table = $this->sendQuestion('Enter the table name');
            if (!in_array($table, $getTables)) {
                $this->sendError("Table not exists", $table ?: 'NULL');
                $confirm = false;
            }
        } while (!$confirm);
        $dirs = [];
        for ($i = 0; $i < count(self::DEFAULT_DIRS); $i++) {
            $dir = self::DEFAULT_DIRS[$i];
            $dirs[self::CLASS_FILE[$dir]] = getcwd() . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $dir;
            mkdir($dirs[self::CLASS_FILE[$dir]], '0777', true);
        }

        $namespaces = [];
        foreach ($dirs as $dir => $path) {
            $namespaces[$dir] = str_replace([getcwd() . DIRECTORY_SEPARATOR . 'app', '.php', DIRECTORY_SEPARATOR], ['Application', '', '\\'], $path);
        }
        $success = [];
        // controller
        $controller = new Controller([], [
            'name' => $name,
            'module' => $module,
            ...$params
        ]);
        if ($file = $controller->process()) {
            $success['Controller'] = $namespaces['Controller'] . '\\' . $file;
        }

        // entity
        $entity = new Entity([], [
            'name' => $name,
            'module' => $module,
            'manager' => $namespaces['Manager'] . "\\" . ucfirst($name . 'Manager'),
            ...$params
        ]);
        if ($file = $entity->process()) {
            $success['Entity'] = $namespaces['Entity'] . '\\' . $file;
        }

        // manager
        $manager = new Manager([], [
            'name' => $name,
            'module' => $module,
            'entity' => $namespaces['Entity'] . "\\" . ucfirst($name),
            'table' => $table,
            ...$params
        ]);
        if ($file = $manager->process()) {
            $success['Manager'] = $namespaces['Manager'] . '\\' . $file;
        }

        if(file_put_contents(getcwd().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'Middlewares'.DIRECTORY_SEPARATOR.'aliases.php', file_get_contents(dirname(__DIR__).'/models/aliases.model'))){
            $success['Aliases file'] = 'app'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'Middlewares'.DIRECTORY_SEPARATOR.'aliases.php';
        }

        // provider
        foreach (['auth', 'app'] as $item) {
            $provider = new Provider([], [
                'name' => $item,
                'module' => $module
            ]);
            if ($file = $provider->process()) {
                $success['Provider'][] = $namespaces['Provider'] . '\\' . $file;
            }
        }

        if(in_array('--crud', $params)){
            $viewPath = 'views' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
            if(!is_dir(getcwd() . DIRECTORY_SEPARATOR . $viewPath)){
                mkdir(getcwd() . DIRECTORY_SEPARATOR . $viewPath, '0777', true);
            }
            foreach (['index', 'show', 'create', 'edit'] as $view) {
                $file = $viewPath.$view.'.html.php';
                if(file_exists(getcwd() . DIRECTORY_SEPARATOR . $file)){
                    continue;
                }
                if(file_put_contents(getcwd() . DIRECTORY_SEPARATOR . $file, '') !== false){
                    $success['View file'][] = $file;
                }
            }
        }

        foreach ($success as $key => $files){
            $files = (array) $files;
            for($i = 0; $i < count($files); $i++){
                $file = $files[$i];
                echo $this->getColoredString("$key created", 'green', 'bold') . ": $file\n";
            }
        }
        return true;
    }
}
