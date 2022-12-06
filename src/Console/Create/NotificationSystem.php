<?php

namespace MkyCore\Console\Create;

class NotificationSystem extends Create
{
    protected string $outputDirectory = 'NotificationSystems';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:NotificationSystem'],
    ];

    public function __construct(array $params = [], array $moduleOptions = [])
    {
        $name = array_shift($params);
        $moduleOptions = array_merge($moduleOptions, ['name' => $name, 'module' => 'root']);
        parent::__construct($params, $moduleOptions);
    }

    public function process(): bool|string
    {
        parent::process();
        $outputDir = $this->getOutPutDir();
        $name = $this->handlerRules('name', $this->moduleOptions['name']);
        return $this->sendSuccess("{$this->createType} created", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
    }
}