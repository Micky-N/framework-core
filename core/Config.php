<?php

namespace MkyCore;

use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class Config
{

    public function __construct(private readonly Application $app, private readonly string $configPath)
    {
    }

    /**
     * Get config name
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $configArray = explode('.', $key);
        $directory = array_shift($configArray);
        $config = $this->getMergedConfig($directory) ?? $default;
        if ($configArray) {
            for ($i = 0; $i < count($configArray); $i++) {
                if (isset($config[$configArray[$i]])) {
                    $config = $config[$configArray[$i]];
                } else {
                    if (!is_null($default)) {
                        return $default;
                    }
                    throw new ConfigNotFoundException("config {$configArray[$i]} do not exists");
                }
            }
        }
        return $config;
    }

    /**
     * Get config merged with module config
     *
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    private function getMergedConfig(string $moduleDirectory): ?array
    {
        $moduleKey = false;
        $moduleConfig = [];
        $moduleDirectory = explode('::', $moduleDirectory);
        if (count($moduleDirectory) == 2) {
            $moduleKey = array_shift($moduleDirectory);
        }
        $directory = array_shift($moduleDirectory);
        if ($moduleKey) {
            $module = $this->app->getModuleKernel($moduleKey);
            $moduleConfig = array_filter($module->getConfig(), fn($c) => str_starts_with($c, "$directory::"), ARRAY_FILTER_USE_KEY);
            $a = array_map(fn($c) => str_replace("$directory::", '', $c), array_keys($moduleConfig));
            $moduleConfig = array_combine($a, $moduleConfig);
        }

        $configFile = $this->configPath . "/$directory.php";
        if (!file_exists($configFile)) {
            return null;
        }
        return array_replace_recursive(include($configFile), $moduleConfig);
    }
}