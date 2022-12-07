<?php

namespace MkyCore\Console\Create;

use MkyCore\Console\Color;
use Exception;

abstract class Create
{

    use Color;

    protected array $rules = [];
    protected string $outputDirectory = '';
    private array $communRules = [
        'module' => ['ucfirst', 'ends:module', 'anti-slash'],
        'name' => 'ucfirst'
    ];
    protected string $createType;

    public function __construct(protected readonly array $params = [], protected array $moduleOptions = [])
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
        if (empty($replaceParams['name']) || empty($replaceParams['module'])) {
            $replaceParams['module'] = !empty($replaceParams['module']) ? $replaceParams['module'] : '';
            if (empty($replaceParams['module']) && count(app()->getModules()) > 1) {
                $type = strtolower($this->createType);
                do {
                    $confirm = true;
                    $module = $this->sendQuestion("In which module do you want to create the $type", 'root');
                    $moduleTest = $module == 'root' ? '' : trim($this->handlerRules('module', $module), '\\/');
                    if ($module && !array_key_exists($moduleTest, app()->getModules())) {
                        $this->sendError("Module not found", $module);
                        $confirm = false;
                    }
                    $replaceParams['module'] = $module == 'root' ? '' : $module;
                } while (!$confirm);
            }
        }
        $replaceParams['module'] = $replaceParams['module'] == 'root' ? '' : $replaceParams['module'];
        $replaceParams['module'] = empty($replaceParams['module']) ? '' : $this->handlerRules('module', $replaceParams['module']);
        $replaceParams['name'] = $this->handlerRules('name', array_shift($params) ?: $replaceParams['name']);
        $name = $replaceParams['name'];
        $outputDir = $this->getOutPutDir($replaceParams['module']);
        if (file_exists($outputDir . DIRECTORY_SEPARATOR . $name . '.php')) {
            return $this->sendError("$this->createType file already exists", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
        }
        $replaceParams = $this->handleQuestions($replaceParams, $params);
        foreach ($replaceParams as $key => $value) {
            if (preg_match("/!$key/", $fileModel)) {
                $fileModel = str_replace("!$key", $value, $fileModel);
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

    protected function sendError(string $message, string $res): bool
    {
        echo "\n" . $this->getColoredString($message, 'red', 'bold') . ": $res\n";
        return false;
    }

    public function getOutPutDir(string $module = ''): string
    {
        $module = trim($this->handlerRules('module', $module), '\\');
        $outputDir = "app" . DIRECTORY_SEPARATOR;
        if ($module) {
            $outputDir .= $module . DIRECTORY_SEPARATOR;
        }
        $outputDir .= $this->outputDirectory;
        return getcwd() . DIRECTORY_SEPARATOR . $outputDir;
    }

    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        return $replaceParams;
    }

    protected function sendSuccess(string $message, string $res): bool
    {
        echo "\n" . $this->getColoredString($message, 'green', 'bold') . ": $res\n";
        return true;
    }

    protected function getModuleAndClass(string $moduleAndClass, string $type, string $suffix = ''): string|bool
    {
        $type = ucfirst($type);
        $params = explode('.', $moduleAndClass);
        if (count($params) == 2) {
            $module = ucfirst($params[0]) . 'Module';
            if (!array_key_exists($module, app()->getModules())) {
                $this->sendError("Module not exists", $params[0]);
                return false;
            }
            $moduleAndClass = ucfirst($params[1]) . ucfirst($suffix);
            $moduleAndClass = "App\\$module\\$type\\$moduleAndClass";
        } elseif (count($params) == 1) {
            $moduleAndClass = ucfirst($params[0]) . ucfirst($suffix);
            $moduleAndClass = "App\\$type\\$moduleAndClass";
        }
        if (!class_exists($moduleAndClass)) {
            $this->sendError("Class not exists", $moduleAndClass);
            return false;
        }
        return $moduleAndClass;
    }
}