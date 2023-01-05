<?php

namespace MkyCore\Log;

use Exception;
use Logger;
use LoggerLevel;
use MkyCore\Application;
use MkyCore\Config;

class Log
{
    private string $logger;

    public function __construct(protected Application $app, protected Config $config)
    {
        $logger = $this->config->get('log.default', 'rootLogger');
        $this->logger = $logger == 'rootLogger' ? '' : $logger;
        Logger::configure($this->getInitConfig());
    }

    private function getInitConfig(): array
    {
        $appenders = $this->config->get('log.appenders', []);
        $loggers = $this->config->get('log.loggers', []);
        $filters = $this->config->get('log.filters', []);
        $renderers = $this->config->get('log.renderers', []);
        $rootLogger = $loggers['rootLogger'] ?? [];
        $config = [];
        if ($appenders) {
            foreach ($appenders as $name => $appender) {
                $config['appenders'][$name] = $this->parseAppender($appender, $filters);
            }
        }
        if ($rootLogger) {
            $config['rootLogger'] = $rootLogger;
        }
        if ($loggers) {
            $config['loggers'] = $loggers;
        }
        if($renderers){
            $config['renderers'] = $renderers;
        }
        return $config;
    }

    private function parseAppender(array $currentAppenderConfig, array $filters = []): array
    {
        $currentAppenderConfig['class'] = 'LoggerAppender' . ucfirst($currentAppenderConfig['class']);
        $layout = $currentAppenderConfig['layout'] ?? 'simple';
        if (is_array($layout)) {
            $currentAppenderConfig['layout']['class'] = 'LoggerLayout' . ucfirst($currentAppenderConfig['layout']['class']);
        } elseif (is_string($layout)) {
            $currentAppenderConfig['layout'] = ['class' => 'LoggerLayout' . ucfirst($layout)];
        }
        if (!empty($currentAppenderConfig['filters'])) {
            $appenderFilters = $currentAppenderConfig['filters'];
            for ($i = 0; $i < count($appenderFilters); $i++) {
                $appenderFilter = $appenderFilters[$i];
                if (isset($filters[$appenderFilter])) {
                    $currentAppenderConfig['filters'][$i] = $this->parseFilter($filters[$appenderFilter]);
                }
            }
        }
        return $currentAppenderConfig;
    }

    private function parseFilter(array $filter): array
    {
        $filter['class'] = 'LoggerFilter' . ucfirst($filter['class']);
        return $filter;
    }

    public function info(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelInfo(), $message, $throwable);
    }

    private function log(LoggerLevel $loggerLevel, mixed $message, ?Exception $throwable = null): void
    {
        $this->getCurrentLogger()->log($loggerLevel, $message, $throwable);
    }

    public function getCurrentLogger(): Logger
    {
        return $this->getLogger($this->logger);
    }

    public function getLogger(string $name): Logger
    {
        return Logger::getLogger($name);
    }

    public function debug(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelDebug(), $message, $throwable);
    }

    public function error(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelError(), $message, $throwable);
    }

    public function fatal(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelFatal(), $message, $throwable);
    }

    public function off(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelOff(), $message, $throwable);
    }

    public function trace(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelTrace(), $message, $throwable);
    }

    public function warn(mixed $message, ?Exception $throwable = null): void
    {
        $this->log(LoggerLevel::getLevelWarn(), $message, $throwable);
    }

    /**
     * @return Logger[]
     */
    public function getLoggers(): array
    {
        return Logger::getCurrentLoggers();
    }

    public function use(string $logger): static
    {
        $this->logger = $logger;
        return $this;
    }
}