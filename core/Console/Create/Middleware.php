<?php

namespace MkyCore\Console\Create;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Middleware extends Create
{
    protected string $outputDirectory = 'Middlewares';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:middleware'],
    ];
    private array $types = ['global', 'module', 'route'];

    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        $params = $this->params;
        $name = reset($params);
        $alias = '';

        if(isset($this->moduleOptions['write']) && !$this->moduleOptions['write']){
            return $replaceParams;
        }

        if (!isset($this->moduleOptions['type'])) {
            do {
                $confirm = true;
                $type = $this->sendQuestion('Enter the type of middleware (' . join('/', $this->types) . ')', 'route') ?: 'route';
                if (!in_array($type, $this->types)) {
                    $this->sendError("Wrong middleware type", $type);
                    $confirm = false;
                }
            } while (!$confirm);
        } else {
            $type = $this->moduleOptions['type'];
        }

        if (!isset($this->moduleOptions['alias'])) {
            if ($type === 'route') {
                do {
                    $confirm = true;
                    $alias = $this->sendQuestion('Enter the middleware alias', $name) ?: $name;
                    if (!$alias) {
                        $this->sendError("No alias entered", 'NULL');
                        $confirm = false;
                    }
                } while (!$confirm);
            }
        } else {
            $alias = $this->moduleOptions['alias'];
        }

        $this->writeInAliasesFile($type, $replaceParams, $alias);
        return $replaceParams;
    }

    /**
     * @param string $type
     * @param array $replaceParams
     * @param string $alias
     * @return bool
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function writeInAliasesFile(string $type, array $replaceParams, string $alias = ''): bool
    {
        $typeMiddlewarefile = $type == 'module' ? 'middlewares' : $type . 'Middlewares';
        $module = $this->app->getModuleKernel($replaceParams['module']);
        $name = $replaceParams['name'];
        $class = $module->getModulePath(true) . "\Middlewares\\$name";
        $file = match ($type) {
            'global' => getcwd() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'aliases.php',
            default => $module->getModulePath() . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'aliases.php',
        };

        $prefix = '\\';
        if ($alias) {
            $prefix = "'$alias' => ";
        }
        $arr = explode("\n", file_get_contents($file));
        $index = array_keys(preg_grep("/'$typeMiddlewarefile' => \[/i", $arr));
        if (!$index) {
            $end = count($arr) - 1;
            $virgule = $end - 1;
            if (str_contains($arr[$virgule], ']')) {
                $arr[$virgule] = str_replace(']', '],', $arr[$virgule]);
            }
            array_splice($arr, $end, 0, "\t'$typeMiddlewarefile' => [\n\t]");
            $arr = implode("\n", $arr);
            file_put_contents($file, $arr);
            $arr = explode("\n", file_get_contents($file));
            $index = array_keys(preg_grep("/'$typeMiddlewarefile' => \[/i", $arr));
        }
        $middlewaresLine = $index[0];
        array_splice($arr, $middlewaresLine + 1, 0, "\t    $prefix$class::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        return file_put_contents($file, $arr) !== false;
    }
}