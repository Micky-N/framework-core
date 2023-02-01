<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Str;
use ReflectionException;

class Entity extends Create
{

    protected string $outputDirectory = 'Entities';
    protected string $createType = 'entity';
    protected string $suffix = 'Entity';

    protected string $description = 'Create a new entity';

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of event, by default is suffixed by Event')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the event');
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): void
    {
        $manager = $this->variables['manager'] ?? false;
        $properties = [];
        if (!$manager) {
            do {
                $confirm = true;
                $manager = trim($input->ask('Enter the name of manager', false, 'n/ to skip'));
                if ($manager) {
                    $confirm = $this->getModuleAndClass($output, $manager, 'managers', 'manager', $vars['module'] ?? '');
                    if ($confirm) {
                        $manager = $confirm;
                    }
                }
            } while (!$confirm);
        }
        do {
            $property = $input->ask('Set property', false, 'n/ to skip');
            if ($property) {
                $properties[] = Str::camelize($property);
            }
        } while ($property);

        do {
            $confirm = true;
            $primaryKey = $input->ask('Set primary key property', false, 'n/ to skip');
            if ($primaryKey) {
                $primaryKey = Str::camelize($primaryKey);
                if (!in_array($primaryKey, $properties)) {
                    $confirm = false;
                }
            }
        } while (!$confirm);

        $propertiesString = '';
        $gettersString = '';
        if ($properties) {
            $propertiesString = $this->setProperties($properties, $primaryKey ?: '');
            $gettersString = $this->setGetters($properties);
        }

        if ($manager) {
            $manager = $this->setManager($manager);
        }
        $vars['manager'] = $manager;
        $vars['properties'] = $propertiesString;
        $vars['getters'] = $gettersString;
    }

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    protected function getModuleAndClass(Output $output, string $moduleClass, string $type, string $end = '', string $moduleAlias = ''): string|bool
    {
        $module = 'App';
        $moduleClass = explode(':', $moduleClass);
        if (count($moduleClass) == 2) {
            $module = array_shift($moduleClass);
            if ($module == '@') {
                $module = $moduleAlias;
            }
            $module = $this->application->getModuleKernel($module);
            if (!$module) {
                $output->error("Module not found", $module);
                return false;
            }
            $module = $module->getModulePath(true);
        }
        $class = [$module, ucfirst($type), ucfirst(array_shift($moduleClass)) . ucfirst($end)];
        $final = join('\\', $class);
        if (!class_exists($final)) {
            $output->error("Class not exists", $final);
            return false;
        }
        return $final;
    }

    private function setProperties(array $properties, string $primaryKey = ''): string
    {
        $props = array_map(function ($prop) use ($primaryKey) {
            $propString = "private \$$prop;";
            if ($primaryKey && $primaryKey === $prop) {
                $propString = <<<PK
/**
    * @PrimaryKey
    */
    $propString
PK;
            }
            return $propString;
        }, $properties);

        return join("\n\t", $props);
    }

    private function setGetters(array $properties): string
    {
        $props = array_map(function ($prop) {
            $set = ucfirst($prop);
            return <<<GETTER
public function $prop()
    {
        return \$this->$prop;
    }
    
    public function set$set(\$$prop)
    {
        \$this->$prop = \$$prop;
    }
GETTER;
        }, $properties);
        return join("\n\n\t", $props);
    }

    private function setManager(string $manager): string
    {
        return <<<MANAGER

/**
 * @Manager('$manager')
 */
MANAGER;
    }
}