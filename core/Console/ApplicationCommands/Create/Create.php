<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;

abstract class Create extends AbstractCommand
{

    protected string $outputDirectory;
    protected string $createType;
    protected string $suffix;

    public function __construct(protected readonly Application $application, protected readonly array $variables = [])
    {
        $pathExplode = explode(DIRECTORY_SEPARATOR, static::class);
        $this->createType = end($pathExplode);
    }


    /**
     * @param Input $input
     * @param Output $output
     * @return string|int
     * @throws CommandException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): string|int
    {
        $vars = [];
        $fileModel = file_get_contents(dirname(__DIR__) . "/models/{$this->createType}.model");
        if (!isset($this->variables['name'])) {
            $name = ucfirst($input->argument('name'));
            if (!$input->option('real')) {
                if (!str_ends_with($name, $this->suffix)) {
                    $name .= $this->suffix;
                }
            }
        } else {
            $name = ucfirst($this->variables['name']);
            if (!str_ends_with($name, $this->suffix)) {
                $name .= $this->suffix;
            }
        }

        if (!isset($this->variables['module'])) {
            if ($this->application->getModules()) {
                $allModules = array_keys($this->application->getModules());
                $moduleIndex = $input->choice("In which module do you want to create the {$this->createType}?", $allModules, 0, 3, fn($index, $modules) => $output->error("Module not found", $modules[$index]));
                $module = $this->application->getModuleKernel($moduleIndex);
            } else {
                $module = $this->application->getModuleKernel('root');
            }
        } else {
            $module = $this->variables['module'];
            $module = $this->application->getModuleKernel($module);
        }
        $namespace = $module->getModulePath(true);
        $outputDir = File::makePath([$module->getModulePath(), $this->outputDirectory]);
        if (file_exists($outputDir . DIRECTORY_SEPARATOR . $name . '.php')) {
            $output->error("$name file already exists", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
            return self::ERROR;
        }

        $vars['name'] = $name;
        $vars['module'] = $namespace;

        $this->gettingStarted($input, $output, $module, $vars);

        foreach ($vars as $key => $var) {
            if (preg_match("/\{\{$key}}/", $fileModel)) {
                $fileModel = str_replace('{{' . $key . '}}', $var, $fileModel);
            }
        }
        $fileModel = preg_replace('/\{\{(\w*)}}/', '', $fileModel);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }

        file_put_contents($outputDir . DIRECTORY_SEPARATOR . $name . '.php', $fileModel);

        if (count($this->variables) > 0) {
            return $namespace . '\\' . $name;
        } else {
            $output->success("$name created", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
            return self::SUCCESS;
        }
    }

    abstract public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars);
}