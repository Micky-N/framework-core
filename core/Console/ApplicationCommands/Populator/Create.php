<?php

namespace MkyCore\Console\ApplicationCommands\Populator;

use MkyCommand\AbstractCommand;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;
use function MkyCore\Console\Populator\str_ends_with;

class Create extends AbstractCommand
{

    protected string $description = 'Create a new populator';

    public function __construct(private readonly Application $application, private readonly array $variables = [])
    {
    }

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of the populator, the name will be suffixed by Populator');
    }

    public function execute(Input $input, Output $output): bool|string
    {
        $outputDir = File::makePath([$this->application->get('path:database'), 'Populators']);

        $name = ucfirst($input->argument('name'));
        if (!str_ends_with($name, 'Populator')) {
            $name .= 'Populator';
        }
        $final = $outputDir . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($final)) {
            $output->error('File already exists', 'Populators' . DIRECTORY_SEPARATOR . "$name.php");
            return self::ERROR;
        }
        $manager = $this->getManagerQuestion($input, $output);
        $class = explode('\\', $manager);
        $class = end($class);
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        $parsedModel = file_get_contents(dirname(__DIR__).'models/populator.model');
        $parsedModel = str_replace('!name', $name, $parsedModel);
        $parsedModel = str_replace('!manager', "$manager", $parsedModel);
        $parsedModel = str_replace('!class', "$class::class", $parsedModel);
        file_put_contents($final, $parsedModel);
        if(count($this->variables) > 0){
            return $final;
        }
        $output->success("Populator file created", $final);
        return self::SUCCESS;
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return string
     * @throws CommandException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getManagerQuestion(Input $input, Output $output): string
    {
        $name = $input->argument('name');
        $managerClass = '';
        do{
            $manager = $input->ask('Enter the manager to link with populator (module:manager, module:@ for current name suffixed by Manager)') ?: false;
            if(!$manager){
                $output->error('No manager given');
                $confirm = false;
            }else{
                $manager = str_replace('@', $name, $manager);
                $managerClass = $this->getModuleAndClass($output, $manager, 'managers', 'Manager');
                $confirm = $managerClass !== false;
            }
        }while(!$confirm);
        return $managerClass;
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