<?php

namespace MkyCore\Abstracts;

use MkyCore\Application;

abstract class ModuleKernel
{
    protected string $parent = '';

    public function __construct(protected Application $app)
    {
    }

    public function getConfig(string $name = null, mixed $default = null): mixed
    {
        $configPath = $this->getModulePath().DIRECTORY_SEPARATOR.'config.php';
        if (!file_exists($configPath)) {
            return [];
        }
        $config = include($configPath);
        return $name ? ($config[$name] ?? $default) : $config;
    }

    public function getAlias(): string
    {
        $modules = $this->app->getModules();
        $kernel = get_class($this);
        return array_search($kernel, $modules, true);

    }

    public function getModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        $shortName = $reflection->getShortName();
        return str_replace("\\$shortName.php", '', $reflection->getFileName());
    }

    public function getParentKernel(): ?ModuleKernel
    {
        if (!class_exists($this->parent)) {
            return null;
        }
        $parent = $this->app->get($this->parent);
        if (!($parent instanceof ModuleKernel)) {
            return null;
        }
        return $parent;

    }

    public function isNestedModule(): bool
    {
        return $this->parent !== '';
    }

}