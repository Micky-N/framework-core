<?php

namespace MkyCore\Log;

use MkyCore\Application;
use MkyCore\Config;

class Log
{
    private string $appender;

    public function __construct(protected Application $app, protected Config $config)
    {
        $this->appender = $this->config->get('log.default');
        dd($this->getInitConfig());
        \Logger::configure($this->getInitConfig());
    }

    private function getInitConfig(): array
    {
        $currentAppenderConfig = $this->config->get('log.appenders.' . $this->appender, []);
        $rootLoggerAppenders = $this->config->get('log.root.appenders', []);
        $config = [];
        if ($currentAppenderConfig) {
            $config[$this->appender] = $this->parseAppender($currentAppenderConfig);
        }
        if ($rootLoggerAppenders) {
            for ($i = 0; $i < count($rootLoggerAppenders); $i++) {
                $rootAppender = $rootLoggerAppenders[$i];
                $rootAppenderConfig = $this->config->get('log.appenders.' . $rootAppender, []);
                if ($rootAppenderConfig) {
                    $config[$rootAppender] = $this->parseAppender($rootAppenderConfig);
                }
            }
        }

        return $config;
    }

    private function parseAppender(array $currentAppenderConfig): array
    {
        $currentAppenderConfig['class'] = 'LoggerAppender' . ucfirst($currentAppenderConfig['class']);
        $layout = $currentAppenderConfig['layout'];
        if (is_array($layout)) {
            $currentAppenderConfig['layout']['class'] = 'LoggerLayout' . ucfirst($currentAppenderConfig['layout']['class']);
        } elseif (is_string($layout)) {
            $currentAppenderConfig['layout']['class'] = 'LoggerLayout' . ucfirst($layout);
        }
        return $currentAppenderConfig;
    }
}