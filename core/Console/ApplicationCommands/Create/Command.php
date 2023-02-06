<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Exceptions\InputArgumentException;
use MkyCommand\Exceptions\InputOptionException;
use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;

class Command extends Create
{
    protected string $outputDirectory = 'Commands';
    protected string $createType = 'command';
    protected string $suffix = 'Command';

    protected string $description = 'Create a new command';


    public function settings(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the command, by default is suffixed by Command')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the command');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return string|null
     * @throws FailedToResolveContainerException
     * @throws InputArgumentException
     * @throws InputOptionException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): ?string
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
            $name = $this->variables['name'];
        }

        $module = $this->application->getModuleKernel('root');
        $namespace = $module->getModulePath(true);
        $outputDir = File::makePath([$module->getModulePath(), $this->outputDirectory]);
        if (file_exists($outputDir . DIRECTORY_SEPARATOR . $name . '.php')) {
            $output->error("$name file already exists", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
            exit();
        }

        $vars['name'] = $name;
        $vars['module'] = $namespace;

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
        }
        return null;
    }

    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars)
    {

    }
}