<?php

namespace MkyCore\Log;

use Logger;
use MkyCore\Application;
use MkyCore\Config;

class Log
{
    private string $logger;

    public function __construct(protected Application $app, protected Config $config)
    {
        $this->logger = $this->config->get('log.default');
        Logger::configure($this->getInitConfig());
    }

    private function getInitConfig(): array
    {
        $appenders = $this->config->get('log.appenders', []);
        $rootLogger = $this->config->get('log.root', []);
        $loggers = $this->config->get('log.loggers', []);
        $config = [];
        if ($appenders) {
            foreach ($appenders as $name => $appender) {
                $config['appenders'][$name] = $this->parseAppender($appender);
            }
        }
        if ($rootLogger) {
            $config['rootLogger'] = $rootLogger;
        }
        if ($loggers) {
            $config['loggers'] = $loggers;
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
            $currentAppenderConfig['layout'] = ['class' => 'LoggerLayout' . ucfirst($layout)];
        }
        return $currentAppenderConfig;
    }

    public function info(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelInfo(), $message, $throwable);
    }

    private function log(\LoggerLevel $loggerLevel, string $message, ?\Exception $throwable = null): void
    {
        $this->getCurrentLogger()->log($loggerLevel, $message, $throwable);
    }

    public function debug(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelDebug(), $message, $throwable);
    }

    public function error(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelError(), $message, $throwable);
    }

    public function fatal(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelFatal(), $message, $throwable);
    }

    public function off(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelOff(), $message, $throwable);
    }

    public function trace(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelTrace(), $message, $throwable);
    }

    public function warn(string $message, ?\Exception $throwable = null): void
    {
        $this->log(\LoggerLevel::getLevelWarn(), $message, $throwable);
    }

    /**
     * @return Logger[]
     */
    public function getLoggers(): array
    {
        return Logger::getCurrentLoggers();
    }
    
    public function getLogger(string $name): Logger
    {
        return Logger::getLogger($name);
    }
    
    public function getCurrentLogger(): Logger
    {
        return $this->getLogger($this->logger);
    }

    public function use(string $logger): static
    {
        $this->logger = $logger;
        return $this;
    }
}