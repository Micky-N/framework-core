<?php

namespace MkyCore\Console\Create;

class Event extends Create
{
    protected string $outputDirectory = 'Events';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:Event'],
    ];

    public function handleQuestions(array $replaceParams, array $params = []): array
    {
        $eventServiceProvider = $this->app->getModuleKernel($replaceParams['module'])->getModulePath(true) .'\Providers\EventServiceProvider';
        if(!class_exists($eventServiceProvider)){
            do{
                $confirm = true;
                $createService = trim($this->sendQuestion('Do you want to create the EventServiceProvider (yes/no)', 'yes')) ?: 'yes';
                if (!in_array($createService, ['yes', 'no'])) {
                    $confirm = false;
                }
            }while(!$confirm);
            if($createService == 'yes'){
                $this->createEventServiceProvider($replaceParams['module']);
            }
        }
        return $replaceParams;
    }

    /**
     * @param string $module
     * @return void
     * @throws \Exception
     */
    private function createEventServiceProvider(string $module): void
    {
        $provider = new Provider([], ['name' => 'event', 'module' => $module]);
        if($provider->process()){
            $message = 'Provider created';
            $module = $this->app->getModuleKernel($module)->getModulePath();
            $res = $this->app->getModuleKernel($module)->getModulePath().DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'EventServiceProvider.php';
            echo "\n" . $this->getColoredString($message, 'green', 'bold') . ": $res";
        }
    }
}