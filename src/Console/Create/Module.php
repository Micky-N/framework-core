<?php

namespace MkyCore\Console\Create;

use MkyCore\Facades\DB;
use MkyCore\File;

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
            $namespaces[$dir] = str_replace([getcwd() . DIRECTORY_SEPARATOR . 'app', '.php', DIRECTORY_SEPARATOR], ['App', '', '\\'], $path);
        }
        $success = [];

        do {
            $confirm = true;
            $alias = $this->sendQuestion('Enter the alias module', $name) ?: $name;
            if ($this->app->hasModule($name)) {
                $this->sendError("Alias $name already exists");
                $confirm = false;
            }
        } while (!$confirm);

        do {
            $confirm = true;
            $routeMode = $this->sendQuestion('Enter the route mode (file/controller/both)', 'controller') ?: 'controller';
            if (!in_array($routeMode, ['file', 'controller', 'both'])) {
                $this->sendError("Route mode not given", $routeMode);
                $confirm = false;
            }
        } while (!$confirm);

        // kernel
        if (($fileKernel = $this->createKernel($name, $module)) && ($configPath = $this->createConfigKernel($name, $routeMode))) {
            $success['Kernel'] = $fileKernel;
            $success['Config'] = $configPath;
        }

        if (!$this->declareModuleInApp($alias, $fileKernel)) {
            $this->sendError('Error in declaration of module in AppServiceProvider');
            return false;
        }

        // controller
        $controller = new Controller($this->app, [], [
            'name' => $name,
            'module' => $alias,
            ...$params
        ]);
        if ($file = $controller->process()) {
            $success['Controller'] = $namespaces['Controller'] . '\\' . $file;
        }

        // entity
        $entity = new Entity($this->app, [], [
            'name' => $name,
            'module' => $alias,
            'manager' => $namespaces['Manager'] . "\\" . ucfirst($name . 'Manager'),
            ...$params
        ]);
        if ($file = $entity->process()) {
            $success['Entity'] = $namespaces['Entity'] . '\\' . $file;
        }

        // manager
        $manager = new Manager($this->app, [], [
            'name' => $name,
            'module' => $alias,
            'entity' => $namespaces['Entity'] . "\\" . ucfirst($name),
            'table' => $table,
            ...$params
        ]);
        if ($file = $manager->process()) {
            $success['Manager'] = $namespaces['Manager'] . '\\' . $file;
        }

        if (file_put_contents(getcwd() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'aliases.php', file_get_contents(dirname(__DIR__) . '/models/aliases.model'))) {
            $success['Aliases file'] = 'app' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'aliases.php';
        }

        // provider
        foreach (['auth', 'app'] as $item) {
            $provider = new Provider($this->app, [], [
                'name' => $item,
                'module' => $alias
            ]);
            if ($file = $provider->process()) {
                $success['Provider'][] = $namespaces['Provider'] . '\\' . $file;
            }
        }

        if ($routeMode != 'controller' && $routePath = $this->createRoutesFile($alias)) {
            $success['Routes file'] = $routePath;
        }

        if (in_array('--crud', $params)) {
            $viewPath = 'views' . DIRECTORY_SEPARATOR;
            $viewsModuleDirectory = $this->viewsModuleDirectory($module);
            if (!is_dir($viewsModuleDirectory . DIRECTORY_SEPARATOR . $viewPath)) {
                mkdir($viewsModuleDirectory . DIRECTORY_SEPARATOR . $viewPath, '0777', true);
            }
            if (!is_dir($viewsModuleDirectory . DIRECTORY_SEPARATOR . $viewPath . DIRECTORY_SEPARATOR . 'layouts')) {
                mkdir($viewsModuleDirectory . DIRECTORY_SEPARATOR . $viewPath . DIRECTORY_SEPARATOR . 'layouts', '0777', true);
            }
            foreach (['index', 'show', 'create', 'edit', 'layouts' . DIRECTORY_SEPARATOR . 'layout'] as $view) {
                $file = $viewPath . $view . '.twig';
                if (file_exists($viewsModuleDirectory . DIRECTORY_SEPARATOR . $file)) {
                    continue;
                }
                if (file_put_contents($viewsModuleDirectory . DIRECTORY_SEPARATOR . $file, '') !== false) {
                    $success['View file'][] = $file;
                }
            }

        }

        foreach ($success as $key => $files) {
            $files = (array)$files;
            for ($i = 0; $i < count($files); $i++) {
                $file = $files[$i];
                echo $this->getColoredString("$key created", 'green', 'bold') . ": $file\n";
            }
        }
        return true;
    }

    private function createKernel(string $name, string $module)
    {
        $name = ucfirst($name);
        $outputDir = $this->app->get('path:app') . DIRECTORY_SEPARATOR . "$module";
        $fileModel = file_get_contents(dirname(__DIR__) . '/models/kernel.model');
        $fileModel = str_replace("!name", $name . 'Kernel', $fileModel);
        $fileModel = str_replace("!module", $module, $fileModel);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        file_put_contents($outputDir . DIRECTORY_SEPARATOR . $name . 'Kernel.php', $fileModel);

        return "App\\$module\\{$name}Kernel";
    }

    private function createConfigKernel(string $name, string $routeMode = 'file')
    {
        $nameModule = ucfirst($name);
        $outputDir = $this->app->get('path:app') . DIRECTORY_SEPARATOR . "{$nameModule}Module";
        $fileModel = file_get_contents(dirname(__DIR__) . '/models/config.model');
        $fileModel = str_replace("!name", $name, $fileModel);
        $fileModel = str_replace("!routeMode", $routeMode, $fileModel);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'config.php', $fileModel);

        return $outputDir . DIRECTORY_SEPARATOR . 'config.php';
    }

    private function declareModuleInApp(string $alias, string $fileKernel)
    {
        $kernel = str_replace([$this->app->get('path:app'), '.php', DIRECTORY_SEPARATOR], ['App', '', '\\'], $fileKernel);
        $file = File::makePath([$this->app->get('path:app'), 'Providers', 'AppServiceProvider.php']);
        $arr = explode("\n", file_get_contents($file));
        $index = array_keys(preg_grep('/private array \$modules = \[/', $arr));
        if (!$index) {
            return false;
        }
        $moduleLine = $index[0];
        array_splice($arr, $moduleLine + 1, 0, "\t    '$alias' => \\$kernel::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        $this->app->addModule($alias, $kernel);
        return file_put_contents($file, $arr) !== false ? $alias : false;
    }

    private function createRoutesFile(string $alias)
    {
        $modulePath = $this->app->getModuleKernel($alias)->getModulePath();
        $model = file_get_contents(dirname(__DIR__) . '/models/routes.model');
        if (!is_dir($modulePath . DIRECTORY_SEPARATOR . 'start')) {
            mkdir($modulePath . DIRECTORY_SEPARATOR . 'start', '0777', true);
        }
        return file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'start' . DIRECTORY_SEPARATOR . 'routes.php', $model) !== false
            ? $modulePath . DIRECTORY_SEPARATOR . 'start' . DIRECTORY_SEPARATOR . 'routes.php' : false;

    }

    private function viewsModuleDirectory(string $module): string
    {
        return $this->app->get('path:app') . DIRECTORY_SEPARATOR . $module;
    }
}
