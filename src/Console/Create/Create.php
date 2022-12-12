<?php

namespace MkyCore\Console\Create;

use Exception;
use MkyCore\Application;
use MkyCore\Console\Color;
use MkyCore\File;

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
     * @throws Exception
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
                $this->sendError('Name not found');
                return false;
            }
            $replaceParams['module'] = !empty($replaceParams['module']) ? $replaceParams['module'] : '';
            if (empty($replaceParams['module']) && count($this->app->getModules()) > 1) {
                $type = strtolower($this->createType);
                do {
                    $confirm = true;
                    $module = $this->sendQuestion("In which module do you want to create the $type", 'root');
                    $module = $module ?: 'root';
                    if (!$this->app->hasModule($module)) {
                        $this->sendError("Module not found", $module);
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
            return $this->sendError("$this->createType file already exists", $outputDir . $name . '.php');
        }
        $replaceParams = $this->handleQuestions($replaceParams, $params);
        $module = $this->app->getModuleKernel($replaceParams['module']);
        $module = new \ReflectionClass($module);
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
        return count($this->moduleOptions) > 0 ? $replaceParams['name'] : $this->sendSuccess("$this->createType created", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
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

    protected function sendError(string $message, string $res = ''): bool
    {
        echo "\n" . $this->getColoredString($message, 'red', 'bold') . ($res ? ": $res" : '') . "\n";
        return false;
    }

    protected function sendQuestion(string $question, string $default = ''): string
    {
        $message = "\n" . $this->getColoredString($question, 'blue', 'bold');
        if ($default) {
            $message .= $this->getColoredString(" [$default]", 'light_yellow');
        }
        $message .= ":\n";
        echo $message;
        return trim((string)readline("> "));
    }

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

    protected function getModuleNamespace(string $module): string
    {
        return $this->app->getModuleKernel($module)->getModulePath(true);
    }

    protected function sendSuccess(string $message, string $res): bool
    {
        echo "\n" . $this->getColoredString($message, 'green', 'bold') . ": $res\n";
        return true;
    }

    protected function getModuleAndClass(string $moduleClass, string $type, string $end = '', string $moduleAlias = ''): string
    {
        $module = 'App';
        $moduleClass = explode(':', $moduleClass);
        if (count($moduleClass) == 2) {
            $module = array_shift($moduleClass);
            if($module == '@'){
                $module = $moduleAlias;
            }
            $module = $this->app->getModuleKernel($module)->getModulePath(true);
        }
        $class = [$module, ucfirst($type), ucfirst(array_shift($moduleClass)) . ucfirst($end)];
        return join('\\', $class);
    }

}