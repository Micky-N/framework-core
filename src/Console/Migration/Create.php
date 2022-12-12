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

    // php mky migration:create create_users_table
    public function process(): bool|string
    {
        $getModel = $this->getModel();
        $output = File::makePath([$this->app->get('path:base'), 'database', 'migrations']);
        $params = $this->params;
        $replaceParams = $this->moduleOptions;
        $name = array_shift($params);
        $prefix = $params['--prefix'] ?? 'create';
        $name = "{$prefix}_{$name}_table";
        $final = $output . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($final)) {
            return $this->sendError("File $final already exists");
        }
        $order = $params['--order'] ?? 1;
        $parsedModel = str_replace('!order', $order > 1 ? "\n\tprotected int \$order = $order;\n" : '', file_get_contents($getModel));
        if (!is_dir($output)) {
            mkdir($output, '0777', true);
        }
        file_put_contents($final, $parsedModel);
        return count($this->moduleOptions) > 0 ? $replaceParams['name'] : $this->sendSuccess("$this->createType file created", $final);
    }

}