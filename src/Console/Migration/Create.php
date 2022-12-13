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
        $table = array_shift($params);
        $params = $this->migrateQuestion($params);
        $type = $params['type'];
        $name = "{$type}_{$table}_table";
        $final = $output . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($final)) {
            return $this->sendError('File already exists', 'migrations' . DIRECTORY_SEPARATOR . "{$name}.php");
        }
        $priority = $params['--priority'] ?? 1;
        $parsedModel = file_get_contents($getModel);
        $parsedModel = str_replace('!priority', $priority > 1 ? "\n\tprotected int \$priority = $priority;\n" : '', $parsedModel);
        $parsedModel = str_replace('!down', $this->setDown($type), $parsedModel);
        $parsedModel = str_replace('!return', $this->setReturn($type), $parsedModel);
        $parsedModel = str_replace('!type', $type, $parsedModel);
        $parsedModel = str_replace('!table', $table, $parsedModel);
        if (!is_dir($output)) {
            mkdir($output, '0777', true);
        }
        file_put_contents($final, $parsedModel);
        return count($this->moduleOptions) > 0 ? $replaceParams['name'] : $this->sendSuccess("$this->createType file created", $final);
    }

    private function migrateQuestion(array $params): array
    {
        $type = '';
        do{
            $confirm = true;
            $type = $this->sendQuestion('Migration file type (create/alter)', 'create') ?: 'create';
            if(!in_array($type, ['create', 'alter'])){
                $this->sendError('Type not right', $type);
                $confirm = false;
            }
        }while(!$confirm);
        $params['type'] = $type;
        return $params;
    }
    
    private function setReturn(string $type): string
    {
        if($type !== 'create'){
            return '//';
        }
        return '$table->id()->autoIncrement()->createRow(),';
    }

    private function setDown(string $type): string
    {
        if($type !== 'create'){
            return '';
        }
        return <<<DOWN

    public function down()
    {
        Schema::drop('!table');
    }
DOWN;
    }

}