<?php

namespace MkyCore\Console\Populator;

use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\File;
use ReflectionException;

class Create extends \MkyCore\Console\Create\Create
{
    protected array $rules = [
        'name' => ['ucfirst', 'ends:populator'],
    ];

    public function __construct(Application $app, array $params = [], array $moduleOptions = [])
    {
        parent::__construct($app, $params, $moduleOptions);
        $this->createType = 'populator';
    }

    public function process(): bool|string
    {
        $getModel = $this->getModel();
        $output = File::makePath([$this->app->get('path:database'), 'populators']);
        $params = $this->params;
        $replaceParams = $this->moduleOptions;
        $namebase = array_shift($params);
        $name = $this->handlerRules('name', $namebase);
        $final = $output . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($final)) {
            return $this->sendError('File already exists', 'populators' . DIRECTORY_SEPARATOR . "{$name}.php");
        }
        $manager = $this->getManagerQuestion($namebase);
        $class = explode('\\', $manager);
        $class = end($class);
        
        if (!is_dir($output)) {
            mkdir($output, '0777', true);
        }
        $parsedModel = file_get_contents($getModel);
        $parsedModel = str_replace('!name', $name, $parsedModel);
        $parsedModel = str_replace('!manager', "$manager", $parsedModel);
        $parsedModel = str_replace('!class', "$class::class", $parsedModel);
        file_put_contents($final, $parsedModel);
        return count($this->moduleOptions) > 0 ? $replaceParams['name'] : $this->sendSuccess("$this->createType file created", $final);
    }

    /**
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    private function getManagerQuestion(string $name): string
    {
        $managerClass = '';
        do{
            $manager = $this->sendQuestion('Enter the manager to link with populator (module:manager, module:@ for current name suffixed by Manager)') ?: false;
            if(!$manager){
                $confirm = $this->sendError('No manager given');
            }else{
                $manager = str_replace('@', $name, $manager);
                $managerClass = $this->getModuleAndClass($manager, 'managers', 'Manager');
                $confirm = $managerClass !== false;
            }
        }while(!$confirm);
        return $managerClass;
    }
}