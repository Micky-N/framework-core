<?php

namespace MkyCore\Console\Create;

use Exception;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;

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
        $params = $this->parseParams();
        $parent = $params['--parent'] ?? 'root';
        $parent = $this->app->getModuleKernel($parent);
        $parentPath = $parent->getModulePath();
        $parentNamespace = $parent->getModulePath(true);
        $name = array_shift($params);
        if (!$name) {
            return $this->error("No name given");
        }
        $module = ucfirst($name) . 'Module';
        $newPath = File::makePath([$parentPath, $module]);
        if (is_dir($newPath)) {
            return $this->error("Module already exists", $module);
        }
        if(empty($this->moduleOptions['alias'])){
            do {
                $confirm = true;
                $alias = $this->ask('Enter the alias module', $name) ?: $name;
                if ($this->app->hasModule($alias)) {
                    $this->error("Alias $alias already exists");
                    $confirm = false;
                }
            } while (!$confirm);
        }else{
            $alias = $this->moduleOptions['alias'];
        }

        if(empty($this->moduleOptions['routeMode'])){
            do {
                $confirm = true;
                $routeMode = $this->ask('Enter the route mode (file/controller/both)', 'controller') ?: 'controller';
                if (!in_array($routeMode, ['file', 'controller', 'both'])) {
                    $confirm = $this->error("Route mode not given", $routeMode);
                }
            } while (!$confirm);
        }else{
            $routeMode = $this->moduleOptions['routeMode'];
        }
        $table = $this->moduleOptions['table'] ?? $this->ask('Enter the table name for manager', 'n/ to skip');
        $dirs = [];
        for ($i = 0; $i < count(self::DEFAULT_DIRS); $i++) {
            $dir = self::DEFAULT_DIRS[$i];
            $dirs[self::CLASS_FILE[$dir]] = "$module\\$dir";
            if(!in_array($dir, ['Entities', 'Managers'])){
                mkdir(File::makePath([$newPath, $dir]), '0777', true);
            }else{
                if($table){
                    mkdir(File::makePath([$newPath, $dir]), '0777', true);
                }
            }
        }

        $namespaces = [];
        foreach ($dirs as $dir => $path) {
            $namespaces[$dir] = $parentNamespace . "\\$path";
        }
        $success = [];

        // kernel
        if (($fileKernel = $this->createKernel($name, $module, $parent)) && ($configPath = $this->createConfigKernel($name, $parent, $routeMode))) {
            $success['Kernel'] = $fileKernel;
            $success['Config'] = $configPath;
        }

        if (!$this->declareModuleInApp($alias, $fileKernel)) {
            return $this->error('Error in declaration of module in AppServiceProvider');
        }

        $parentAlias = $this->getAncestorsAlias($this->app->get($fileKernel));
        // controller
        $controller = new Controller($this->app, [], [
            'name' => $name,
            'module' => $alias,
            'parent' => $parentAlias,
            ...$params
        ]);
        if ($file = $controller->process()) {
            $success['Controller'] = $namespaces['Controller'] . '\\' . $file;
        }

        if($table){
            // entity
            echo "\n".$this->coloredMessage('Entity Creation', 'light_purple', 'bold');
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
        }

        if (file_put_contents(File::makePath([$parentPath, $module, 'Middlewares', 'aliases.php']), file_get_contents(dirname(__DIR__) . '/models/aliases.model'))) {
            $success['Aliases file'] = str_replace(getcwd(), '', File::makePath([$parentPath, $module, 'Middlewares', 'aliases.php']));
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
            $viewsModuleDirectory = $this->viewsModuleDirectory($alias);
            if (!is_dir($viewsModuleDirectory)) {
                mkdir($viewsModuleDirectory, '0777', true);
            }
            if (!is_dir($viewsModuleDirectory . DIRECTORY_SEPARATOR . 'layouts')) {
                mkdir($viewsModuleDirectory . DIRECTORY_SEPARATOR . 'layouts', '0777', true);
            }
            foreach (['index', 'show', 'create', 'edit', 'layouts' . DIRECTORY_SEPARATOR . 'layout'] as $view) {
                $file = $view . '.twig';
                if (file_exists($viewsModuleDirectory . DIRECTORY_SEPARATOR . $file)) {
                    continue;
                }
                if (file_put_contents($viewsModuleDirectory . DIRECTORY_SEPARATOR . $file, '') !== false) {
                    $success['View file'][] = $file;
                }
            }

        }

        echo "\n";
        foreach ($success as $key => $files) {
            $files = (array)$files;
            for ($i = 0; $i < count($files); $i++) {
                $file = $files[$i];
                echo $this->coloredMessage("$key created", 'green', 'bold') . ": $file\n";
            }
        }
        return true;
    }

    /**
     * @param string $name
     * @param string $module
     * @param ModuleKernel $parentKernel
     * @return string
     */
    private function createKernel(string $name, string $module, ModuleKernel $parentKernel): string
    {
        $name = ucfirst($name);
        $outputDir = File::makePath([$parentKernel->getModulePath(), $module]);
        $fileModel = file_get_contents(dirname(__DIR__) . '/models/kernel.model');
        $fileModel = str_replace("!name", $name . 'Kernel', $fileModel);
        $fileModel = str_replace("!module", $parentKernel->getModulePath(true) . "\\$module", $fileModel);
        $parentAlias = $this->setParentAlias($parentKernel);
        $fileModel = str_replace("!parent", $parentAlias, $fileModel);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        file_put_contents(File::makePath([$outputDir, $name . 'Kernel.php']), $fileModel);

        return "{$parentKernel->getModulePath(true)}\\$module\\{$name}Kernel";
    }

    private function setParentAlias(ModuleKernel $parentKernel): string
    {
        $res = '';
        $alias = $parentKernel->getAlias();
        if ($alias !== 'root') {
            $res = "protected string \$parent = '$alias';";
        }
        return $res;
    }

    /**
     * @param string $name
     * @param ModuleKernel $parentKernel
     * @param string $routeMode
     * @return bool|string
     */
    private function createConfigKernel(string $name, ModuleKernel $parentKernel, string $routeMode = 'file'): bool|string
    {
        $nameModule = ucfirst($name);
        $prefix = $this->getAncestorsAlias($parentKernel, '/');
        $prefix = $prefix ? "$prefix/{$parentKernel->getAlias()}/$name" : $name;
        $outputDir = File::makePath([$parentKernel->getModulePath(), "{$nameModule}Module"]);
        $fileModel = file_get_contents(dirname(__DIR__) . '/models/config.model');
        $fileModel = str_replace("!name", strtolower($prefix), $fileModel);
        $fileModel = str_replace("!routeMode", $routeMode, $fileModel);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        file_put_contents(File::makePath([$outputDir, 'config.php']), $fileModel);

        return File::makePath([$outputDir, 'config.php']);
    }

    /**
     * @param string $alias
     * @param string $fileKernel
     * @return bool|string
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws Exception
     */
    private function declareModuleInApp(string $alias, string $fileKernel): bool|string
    {
        $file = File::makePath([$this->app->get('path:app'), 'Providers', 'AppServiceProvider.php']);
        $arr = explode("\n", file_get_contents($file));
        $findModule = array_keys(preg_grep('/private array \$modules = \[/', $arr));
        $arr2 = array_slice($arr, $findModule[0], count($arr) -1, true);
        $index = array_keys(preg_grep("/\];/", $arr2));
        if (!$index) {
            return false;
        }
        $moduleLine = $index[0];
        array_splice($arr, $moduleLine, 0, "\t    '$alias' => \\$fileKernel::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        $this->app->addModule($alias, $fileKernel);
        return file_put_contents($file, $arr) !== false ? $alias : false;
    }

    /**
     * @param string $alias
     * @return bool|string
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function createRoutesFile(string $alias): bool|string
    {
        $modulePath = $this->app->getModuleKernel($alias)->getModulePath();
        $model = file_get_contents(dirname(__DIR__) . '/models/routes.model');
        if (!is_dir($modulePath . DIRECTORY_SEPARATOR . 'start')) {
            mkdir($modulePath . DIRECTORY_SEPARATOR . 'start', '0777', true);
        }
        return file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'start' . DIRECTORY_SEPARATOR . 'routes.php', $model) !== false
            ? $modulePath . DIRECTORY_SEPARATOR . 'start' . DIRECTORY_SEPARATOR . 'routes.php' : false;

    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    private function viewsModuleDirectory(string $alias): string
    {
        $module = $this->app->getModuleKernel($alias);
        return File::makePath([$module->getModulePath(), 'views']);
    }
    
    private function getAncestorsAlias(ModuleKernel $parentKernel, string $join = '.'): string
    {
        if(!$parentKernel->isNestedModule()){
            return '';
        }
        return array_reduce(array_reverse($parentKernel->getAncestorsKernel()), function($a, ModuleKernel $b) use($join){
            $a .= $a ? "$join{$b->getAlias()}" : $b->getAlias();
            return $a;
        });
    }
}
