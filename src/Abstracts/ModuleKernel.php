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
        $configPath = $this->getModulePath() . DIRECTORY_SEPARATOR . 'config.php';
        if (!file_exists($configPath)) {
            return [];
        }
        $config = include($configPath);
        return $name ? ($config[$name] ?? $default) : $config;
    }

    public function getModulePath(bool $namespaced = false): string
    {
        $reflection = new \ReflectionClass($this);
        $shortName = $reflection->getShortName();
        $res = '';
        if ($namespaced) {
            $res = str_replace("\\$shortName", '', $reflection->getNamespaceName());
        } else {
            $res = str_replace("\\$shortName.php", '', $reflection->getFileName());
        }
        return $res;
    }

    public function getAlias(): string
    {
        $modules = $this->app->getModules();
        $kernel = get_class($this);
        return array_search($kernel, $modules, true);

    }

    public function getAncestorsKernel(int $limit = 0): array
    {
        $ancestors = [];
        $ancestors = $this->getAncestorsRecursive($ancestors, $limit);
        return $ancestors;
    }

    private function getAncestorsRecursive(array &$ancestors = [], int $limit = 0): array
    {
        if ($limit > 0 && count($ancestors) >= $limit) {
            return $ancestors;
        }
        if ($this->isNestedModule()) {
            $parent = $this->getParentKernel();
            $ancestors[] = $parent;
            if ($parent->isNestedModule()) {
                return $parent->getAncestorsRecursive($ancestors, $limit);
            }
        }
        return $ancestors;
    }

    public function isNestedModule(): bool
    {
        return $this->parent !== '';
    }

    public function getParentKernel(): ?ModuleKernel
    {
        if (!class_exists($this->app->getModule($this->parent))) {
            return null;
        }
        $parent = $this->app->getModuleKernel($this->parent);
        if (!($parent instanceof ModuleKernel)) {
            return null;
        }
        return $parent;
    }

}