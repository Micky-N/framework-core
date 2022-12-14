<?php

namespace MkyCore\Console\Migration;

use MkyCore\Application;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\File;

class Create extends AbstractCreate
{
    protected array $rules = [
        'name' => ['ucfirst', 'ends:controller'],
    ];

    public function __construct(Application $app, array $params = [], array $moduleOptions = [])
    {
        parent::__construct($app, $params, $moduleOptions);
        $this->createType = 'migration';
    }

    public function process(): bool|string
    {
        $getModel = $this->getModel();
        $output = File::makePath([$this->app->get('path:database'), 'migrations']);
        $params = $this->params;
        $replaceParams = $this->moduleOptions;
        $name = array_shift($params);
        $nameSnaked = $this->toSnake($name);
        $nameFile = time() . "_{$nameSnaked}";
        $final = $output . DIRECTORY_SEPARATOR . $nameFile . '.php';
        if (file_exists($final)) {
            return $this->sendError('File already exists', 'migrations' . DIRECTORY_SEPARATOR . "{$nameFile}.php");
        }
        $parsedModel = file_get_contents($getModel);
        $parsedModel = str_replace('!name', $name, $parsedModel);
        if (!is_dir($output)) {
            mkdir($output, '0777', true);
        }
        file_put_contents($final, $parsedModel);
        return count($this->moduleOptions) > 0 ? $replaceParams['name'] : $this->sendSuccess("$this->createType file created", $final);
    }
    
    private function toSnake(string $name): string
    {
        return preg_replace_callback('/[A-Z+]/', function($exp){
            if(isset($exp[0])){
                return '_'.lcfirst($exp[0]);
            }
            return $exp[0];
        }, lcfirst($name));
    }

}