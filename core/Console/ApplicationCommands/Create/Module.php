<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use Exception;
use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;

class Module extends AbstractCommand
{

    const ROUTE_MODE = ['controller', 'file', 'both'];
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

    protected string $description = 'Create a new module';

    public function __construct(private readonly Application $application)
    {
    }

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of the module, this name will be suffixed by Module')
            ->addOption('crud', 'c', InputOption::NONE, 'Crud implementation for views and controller')
            ->addOption('crud-api', 'a', InputOption::NONE, 'Api Crud implementation for controller')
            ->addOption('parent', 'p', InputOption::OPTIONAL, 'The Module parent name');
    }


    /**
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws CommandException
     */
    public function execute(Input $input, Output $output): int
    {
        $parent = $input->option('parent');
        $parent = $this->application->getModuleKernel($parent);
        $parentPath = $parent->getModulePath();
        $parentNamespace = $parent->getModulePath(true);
        $name = $input->argument('name');
        $module = ucfirst($name) . 'Module';
        $newPath = File::makePath([$parentPath, $module]);
        if (is_dir($newPath)) {
            $output->error("Module already exists", $module);
            return self::ERROR;
        }

        do {
            $confirm = true;
            $alias = $input->ask('Enter the alias module', $name);
            if ($this->application->hasModule($alias)) {
                $output->error("Alias $alias already exists");
                $confirm = false;
            }
        } while (!$confirm);

        $routeMode = $input->choice('Enter the route mode', self::ROUTE_MODE, 0, 3, fn($answer, $c) => $output->error("Route mode not given", $c[$answer]));

        $table = $input->ask('Enter the table name for manager', false, 'n/ to skip');
        $dirs = [];
        for ($i = 0; $i < count(self::DEFAULT_DIRS); $i++) {
            $dir = self::DEFAULT_DIRS[$i];
            $dirs[self::CLASS_FILE[$dir]] = "$module\\$dir";
            if (!in_array($dir, ['Entities', 'Managers'])) {
                mkdir(File::makePath([$newPath, $dir]), '0777', true);
            } else {
                if ($table) {
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
            $output->error('Error in declaration of module in AppServiceProvider');
            return self::ERROR;
        }

        $parentAlias = $this->getAncestorsAlias($this->application->get($fileKernel));
        // controller
        $variables = [
            'name' => $name,
            'module' => $alias,
            'parent' => $parentAlias
        ];
        if($input->hasOption('crud')){
            $variables['crud'] = $input->option('crud');
        }
        if($input->hasOption('crud-api')){
            $variables['crud-api'] = $input->option('crud-api');
        }
        $controller = new Controller($this->application, $variables);
        if ($file = $controller->execute($input, $output)) {
            $success['Controller'] = $namespaces['Controller'] . '\\' . $file;
        }

        if ($table) {
            // entity
            echo "\n" . $output->coloredMessage('Entity Creation', 'light_purple', 'bold');
            $entity = new Entity($this->application, [
                'name' => $name,
                'module' => $alias,
                'manager' => $namespaces['Manager'] . "\\" . ucfirst($name . 'Manager')
            ]);
            if ($file = $entity->execute($input, $output)) {
                $success['Entity'] = $namespaces['Entity'] . '\\' . $file;
            }

            // manager
            $manager = new Manager($this->application, [
                'name' => $name,
                'module' => $alias,
                'entity' => $namespaces['Entity'] . "\\" . ucfirst($name),
                'table' => $table,
            ]);
            if ($file = $manager->execute($input, $output)) {
                $success['Manager'] = $namespaces['Manager'] . '\\' . $file;
            }
        }

        if (file_put_contents(File::makePath([$parentPath, $module, 'Middlewares', 'aliases.php']), file_get_contents(dirname(__DIR__) . '/models/aliases.model'))) {
            $success['Aliases file'] = str_replace(getcwd(), '', File::makePath([$parentPath, $module, 'Middlewares', 'aliases.php']));
        }

        // provider
        foreach (['auth', 'app'] as $item) {
            $provider = new Provider($this->application, [
                'name' => $item,
                'module' => $alias
            ]);
            if ($file = $provider->execute($input, $output)) {
                $success['Provider'][] = $namespaces['Provider'] . '\\' . $file;
            }
        }

        if ($routeMode != 'controller' && $routePath = $this->createRoutesFile($alias)) {
            $success['Routes file'] = $routePath;
        }

        if ($input->hasOption('crud')) {
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
                $output->success("$key created", $file);
            }
        }
        return self::SUCCESS;
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
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
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
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    private function getAncestorsAlias(ModuleKernel $Kernel, string $join = '.'): string
    {
        if (!$Kernel->isNestedModule()) {
            return '';
        }
        return array_reduce(array_reverse($Kernel->getAncestorsKernel()), function ($a, ModuleKernel $b) use ($join) {
            $a .= $a ? "$join{$b->getAlias()}" : $b->getAlias();
            return $a;
        });
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
        $file = File::makePath([$this->application->get('path:app'), 'Providers', 'AppServiceProvider.php']);
        $arr = explode("\n", file_get_contents($file));
        $findModule = array_keys(preg_grep('/private array \$modules = \[/', $arr));
        $arr2 = array_slice($arr, $findModule[0], count($arr) - 1, true);
        $index = array_keys(preg_grep("/];/", $arr2));
        if (!$index) {
            return false;
        }
        $moduleLine = $index[0];
        array_splice($arr, $moduleLine, 0, "\t    '$alias' => \\$fileKernel::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        $this->application->addModule($alias, $fileKernel);
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
        $modulePath = $this->application->getModuleKernel($alias)->getModulePath();
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
        $module = $this->application->getModuleKernel($alias);
        return File::makePath([$module->getModulePath(), 'views']);
    }
}
