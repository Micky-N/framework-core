<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Middleware extends Create
{
    protected string $outputDirectory = 'Middlewares';
    protected string $createType = 'middleware';
    protected string $suffix = 'Middleware';

    private const TYPES = ['route', 'global', 'module'];

    protected string $description = 'Create a new middleware';

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of the middleware, by default is suffixed by Middleware')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the middleware');
    }

    /**
     * @param ModuleKernel $module
     * @param string $name
     * @param string $type
     * @param string $alias
     * @return bool
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function writeInAliasesFile(ModuleKernel $module, string $name, string $type, string $alias = ''): bool
    {
        $typeMiddlewareFile = $type == 'module' ? 'middlewares' : $type . 'Middlewares';
        $class = $module->getModulePath(true) . "\Middlewares\\$name";
        $file = match ($type) {
            'global' => $this->application->get('path:app') . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'aliases.php',
            default => $module->getModulePath() . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'aliases.php',
        };

        $prefix = '\\';
        if ($alias) {
            $prefix = "'$alias' => ";
        }
        $arr = explode("\n", file_get_contents($file));
        $findType = array_keys(preg_grep("/'$typeMiddlewareFile' => \[/i", $arr));
        if (!$findType) {
            $end = count($arr) - 1;
            $virgule = $end - 1;
            if (str_contains($arr[$virgule], ']')) {
                $arr[$virgule] = str_replace(']', '],', $arr[$virgule]);
            }
            array_splice($arr, $end, 0, "\t'$typeMiddlewareFile' => [\n\t]");
            $arr = implode("\n", $arr);
            file_put_contents($file, $arr);
            $arr = explode("\n", file_get_contents($file));
            $findType = array_keys($p = preg_grep("/'$typeMiddlewareFile' => \[/i", $arr));
        }
        $arr2 = array_slice($arr, $findType[0], count($arr) - 1, true);
        $index = array_keys(preg_grep("/],/", $arr2))[0];
        array_splice($arr, $index, 0, "\t    $prefix$class::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        return file_put_contents($file, $arr) !== false;
    }

    /**
     * @param Input $input
     * @param Output $output
     * @param ModuleKernel $moduleKernel
     * @param array $vars
     * @return bool
     * @throws CommandException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): bool
    {
        $name = $this->variables['name'] ?? $input->argument('name');
        $alias = '';

        if (isset($this->variables['write']) && !$this->variables['write']) {
            return false;
        }

        if (!isset($this->variables['type'])) {
            $type = $input->choice('Select the type of the middleware', self::TYPES, 0, 3);
        } else {
            $type = $this->variables['type'];
        }

        if (!isset($this->variables['alias'])) {
            if ($type === 'route') {
                $alias = $input->ask('Enter the middleware alias', $name);
            }
        } else {
            $alias = $this->variables['alias'];
        }

        return $this->writeInAliasesFile($moduleKernel, $name, $type, $alias);
    }
}