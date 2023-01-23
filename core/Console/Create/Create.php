<?php

namespace MkyCore\Console\Create;

use MkyCommand\Color;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;

abstract class Create
{

    use Color;

    protected array $rules = [];
    protected string $outputDirectory = '';
    protected string $createType;
    private array $communRules = [
        'name' => 'ucfirst'
    ];

    public function __construct(protected Application $app, protected readonly array $params = [], protected array $moduleOptions = [])
    {
        $pathExplode = explode(DIRECTORY_SEPARATOR, static::class);
        $this->createType = end($pathExplode);
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     */
    public function process(): bool|string
    {
        $getModel = $this->getModel();
        $fileModel = file_get_contents($getModel);
        $params = array_values($this->params);
        $replaceParams = $this->moduleOptions;
        $replaceParams['name'] = $this->handlerRules('name', array_shift($params) ?: $replaceParams['name']);
        if (empty($replaceParams['name']) || empty($replaceParams['module'])) {
            if (!isset($params['name']) && !isset($replaceParams['name'])) {
                $this->error('Name not found');
                return false;
            }
            $replaceParams['module'] = !empty($replaceParams['module']) ? $replaceParams['module'] : '';
            if (empty($replaceParams['module']) && count($this->app->getModules()) > 1) {
                $type = strtolower($this->createType);
                do {
                    $confirm = true;
                    $module = $this->ask("In which module do you want to create the $type", 'root') ?: 'root';
                    if (!$this->app->hasModule($module)) {
                        $this->error("Module not found", $module);
                        $confirm = false;
                    }
                    $replaceParams['module'] = $module;
                } while (!$confirm);
            }
        }
        $replaceParams['module'] ??= 'root';

        $name = $replaceParams['name'];
        $outputDir = $this->getOutPutDir($replaceParams['module']);
        if (file_exists($outputDir . $name . '.php')) {
            return $this->error("$this->createType file already exists", $outputDir . $name . '.php');
        }
        $replaceParams = $this->handleQuestions($replaceParams, $params);
        foreach ($replaceParams as $key => $value) {
            if (preg_match("/!$key/", $fileModel)) {
                if ($key == 'module') {
                    $fileModel = str_replace("!$key", $this->getModuleNamespace($value), $fileModel);
                } else {
                    $fileModel = str_replace("!$key", $value, $fileModel);
                }
            } else {
                $fileModel = str_replace("!$key", '', $fileModel);
            }
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        file_put_contents($outputDir . DIRECTORY_SEPARATOR . $name . '.php', $fileModel);
        return count($this->moduleOptions) > 0 ? $replaceParams['name'] : $this->success("$this->createType created", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
    }

    public function getModel(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . lcfirst($this->createType . '.model');
    }

    public function handlerRules($key, $value): mixed
    {
        $rules = array_merge_recursive($this->communRules, $this->rules);
        if (!array_key_exists($key, $rules) || !$value) {
            return $value;
        }
        $rulesKey = (array)$rules[$key];
        for ($i = 0; $i < count($rulesKey); $i++) {
            $rule = $rulesKey[$i];
            if ($rule == 'anti-slash') {
                $value .= '\\';
            } elseif ($rule == 'dbl-anti-slash') {
                $value .= '\\\\';
            } elseif ($rule == 'ucfirst') {
                $value = ucfirst($value);
            } elseif (preg_match('/ends:[\w]+/', $rule)) {
                $end = ucfirst(str_replace('ends:', '', $rule));
                if (!str_contains($value, $end)) {
                    $value = $value . $end;
                }
            }
        }
        return $value;
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    protected function getOutPutDir(string $module = 'root'): string
    {
        $module = $this->app->getModuleKernel($module);
        $modulePath = $module->getModulePath();
        return File::makePath([$modulePath, $this->outputDirectory]);
    }

    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        return $replaceParams;
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    protected function getModuleNamespace(string $module): string
    {
        return $this->app->getModuleKernel($module)->getModulePath(true);
    }

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    protected function getModuleAndClass(string $moduleClass, string $type, string $end = '', string $moduleAlias = ''): string|bool
    {
        $module = 'App';
        $moduleClass = explode(':', $moduleClass);
        if (count($moduleClass) == 2) {
            $module = array_shift($moduleClass);
            if ($module == '@') {
                $module = $moduleAlias;
            }
            $module = $this->app->getModuleKernel($module);
            if (!$module) {
                return $this->error("Module not found", $module);
            }
            $module = $module->getModulePath(true);
        }
        $class = [$module, ucfirst($type), ucfirst(array_shift($moduleClass)) . ucfirst($end)];
        $final = join('\\', $class);
        if (!class_exists($final)) {
            return $this->error("Class not exists", $final);
        }
        return $final;
    }

    protected function parseParams(): array
    {
        $params = [];
        foreach ($this->params as $index => $param) {
            $param = explode('=', $param);
            if (count($param) == 2) {
                $index = array_shift($param);
            }
            $params[$index] = array_shift($param);
        }
        return $params;
    }

}