<?php

namespace MkyCore\Console\Create;

use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Str;
use ReflectionException;

class Manager extends Create
{
    protected string $outputDirectory = 'Managers';
    protected string $createType = 'manager';
    protected string $suffix = 'Manager';

    protected string $description = 'Create a new manager';

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of the manager, the name is suffixed by Manager');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @param ModuleKernel $moduleKernel
     * @param array $vars
     * @return void
     * @throws CommandException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): void
    {
        $table = $this->variables['table'] ?? false;
        if (!$table) {
            $name = strtolower(Str::pluralize(str_replace('Manager', '', $input->argument('name'))));
            $table = $input->ask('Enter the table name', $name);
        }

        $entity = $this->variables['entity'] ?? false;
        if (!$entity) {
            do {
                $confirm = true;
                $entity = trim($input->ask('Enter the name of entity', false, 'n/ to skip'));
                if ($entity) {
                    $confirm = $this->getModuleAndClass($output, $entity, 'entities', '', $vars['module'] ?? '');
                    if ($confirm) {
                        $entity = $confirm;
                    }
                }
            } while (!$confirm);
        }

        $vars['table'] = $this->setTable($table, $entity);
    }

    protected function setTable(string $table, string $entity = ''): string
    {
        $res = "\n/**\n";
        $res .= " * @Table('$table')\n";
        if ($entity) {
            $res .= " * @Entity('$entity')\n";
        }
        $res .= " */";
        return $res;

    }

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    protected function getModuleAndClass(Output $output, string $moduleClass, string $type, string $end = '', string $moduleAlias = ''): string|bool
    {
        $module = 'root';
        $moduleClass = explode(':', $moduleClass);
        if (count($moduleClass) == 2) {
            $module = array_shift($moduleClass);
            if ($module == '@') {
                $module = $moduleAlias;
            }
        }
        $module = $this->application->getModuleKernel($module);
        if (!$module) {
            $output->error("Module not found", $module);
            return false;
        }
        $module = $module->getModulePath(true);
        $class = [$module, ucfirst($type), ucfirst(array_shift($moduleClass)) . ucfirst($end)];
        $final = join('\\', $class);
        if (!class_exists($final)) {
            $output->error("Class not exists", $final);
            return false;
        }
        return $final;
    }
}