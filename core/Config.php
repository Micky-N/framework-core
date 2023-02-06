<?php

namespace MkyCore;

use Exception;
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
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $configArray = explode('.', $key);
        $directory = array_shift($configArray);
        try {
            $config = $this->getConfig($directory, true) ?? $default;
        } catch (Exception) {
            return $default;
        }
        return $this->getParamConfig($configArray, $config, $default);
    }

    /**
     * @param string $directory
     * @param bool $merged
     * @return array
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getConfig(string $directory, bool $merged = false): array
    {
        if (!$merged) {
            $configFile = $this->configPath . "/$directory.php";
            if (!file_exists($configFile)) {
                return [];
            }
            return include($configFile);
        }
        return $this->getMergedConfig($directory);
    }

    /**
     * Get config merged with module config
     *
     * @param string $directory
     * @return array
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getMergedConfig(string $directory): array
    {
        $moduleConfig = [];
        $currentRoute = $this->app->getCurrentRoute();

        if ($currentRoute) {
            $currentModule = $this->app->getModuleKernel($currentRoute->getModule());
            if ($currentModule->isNestedModule()) {
                $parentModules = $currentModule->getAncestorsKernel();
                $parentModules[] = $currentModule;
                for ($i = 0; $i < count($parentModules); $i++) {
                    $module = $parentModules[$i];
                    $filterConfig = $this->getFilterConfig($module, $directory);
                    $moduleConfig = array_merge($moduleConfig, $filterConfig);
                }
            } else {
                $filterConfig = $this->getFilterConfig($currentModule, $directory);
                $moduleConfig = array_merge($moduleConfig, $filterConfig);
            }

        }

        $configFile = $this->configPath . "/$directory.php";
        if (!file_exists($configFile)) {
            return [];
        }
        return array_replace_recursive(include($configFile), $moduleConfig);
    }

    /**
     * @param Abstracts\ModuleKernel|null $module
     * @param string $directory
     * @return array
     */
    private function getFilterConfig(?Abstracts\ModuleKernel $module, string $directory): array
    {
        $config = $module->getConfig();
        $filterConfig = array_filter($config, fn($c) => str_starts_with($c, "$directory:"), ARRAY_FILTER_USE_KEY);
        $a = array_map(fn($c) => str_replace("$directory:", '', $c), array_keys($filterConfig));
        return array_combine($a, $filterConfig);
    }

    /**
     * @param array $configArray
     * @param mixed $config
     * @param mixed $default
     * @return mixed
     * @throws ConfigNotFoundException
     */
    private function getParamConfig(array $configArray, mixed $config, mixed $default): mixed
    {
        if ($configArray) {
            for ($i = 0; $i < count($configArray); $i++) {
                if (isset($config[$configArray[$i]])) {
                    $config = $config[$configArray[$i]];
                } else {
                    if (!is_null($default)) {
                        return $default;
                    }
                    throw new ConfigNotFoundException("config $configArray[$i] do not exists");
                }
            }
        }
        return $config;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws ConfigNotFoundException
     */
    public function getBase(string $key, mixed $default = null): mixed
    {
        $configArray = explode('.', $key);
        $directory = array_shift($configArray);
        try {
            $config = $this->getConfig($directory) ?? $default;
        } catch (Exception) {
            return $default;
        }
        return $this->getParamConfig($configArray, $config, $default);
    }
}