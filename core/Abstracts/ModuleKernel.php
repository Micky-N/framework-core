<?php

namespace MkyCore\Abstracts;

use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;

abstract class ModuleKernel
{
    /**
     * Parent module
     *
     * @var string
     */
    protected string $parent = '';

    public function __construct(protected Application $app)
    {
    }

    /**
     * Get module config
     *
     * @param string|null $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getConfig(string $name = null, mixed $default = null): mixed
    {
        $configPath = $this->getModulePath() . DIRECTORY_SEPARATOR . 'config.php';
        if (!file_exists($configPath)) {
            return [];
        }
        $config = include($configPath);
        return $name ? ($config[$name] ?? $default) : $config;
    }

    /**
     * Get module path or namespace
     *
     * @param bool $namespaced
     * @return string
     */
    public function getModulePath(bool $namespaced = false): string
    {
        $reflection = new \ReflectionClass($this);
        $shortName = $reflection->getShortName();
        if ($namespaced) {
            $res = str_replace("\\$shortName", '', $reflection->getNamespaceName());
        } else {
            $res = str_replace("\\$shortName.php", '', $reflection->getFileName());
        }
        return $res;
    }

    /**
     * Get module alias
     *
     * @return string
     */
    public function getAlias(): string
    {
        $modules = $this->app->getModules();
        $kernel = get_class($this);
        return array_search($kernel, $modules, true);

    }

    /**
     * Get ancestors kernel
     * if limit is greater than 0, the maximum size of value will be equal to limit
     *
     * @param int $limit
     * @return array
     */
    public function getAncestorsKernel(int $limit = 0): array
    {
        $ancestors = [];
        return $this->getAncestorsRecursive($ancestors, $limit);
    }

    /**
     * Get ancestors recursively up to the highest parent
     *
     * @param array $ancestors
     * @param int $limit
     * @return array
     */
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

    /**
     * Check if module has parent
     *
     * @return bool
     */
    public function isNestedModule(): bool
    {
        return $this->parent !== '';
    }

    /**
     * Get parent kernel
     *
     * @return ModuleKernel|null
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws \ReflectionException
     */
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

    /**
     * Get parent name
     *
     * @return string
     */
    public function getParent(): string
    {
        return $this->parent;
    }

}