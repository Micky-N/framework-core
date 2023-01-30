<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Router\Route;
use ReflectionClass;
use ReflectionException;

class Application extends Container
{

    /**
     * @var array<string, string>
     */
    private array $modules = [];

    private string $basePath;

    private ?Route $currentRoute = null;

    /**
     * @var array<string, array>
     */
    private array $events = [];


    private string $environmentFile;

    /**
     * @param string $basePath
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function __construct(string $basePath)
    {
        $this->setBasePath($basePath);
        $this->setPathsInContainer();
        $this->registerBaseBindings();
        $this->setInitModules();
        $this->registerServiceProviders();
        $this->loadEnvironment($basePath . DIRECTORY_SEPARATOR . '.env');
    }

    /**
     * Set application paths
     *
     * @param string $basePath
     * @return void
     */
    private function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '\/');
    }

    /**
     * Set paths in container
     *
     * @return void
     */
    private function setPathsInContainer(): void
    {
        $this->setInstance('path:base', $this->basePath);
        $this->setInstance('path:app', $this->basePath . DIRECTORY_SEPARATOR . 'app');
        $this->setInstance('path:config', $this->basePath . DIRECTORY_SEPARATOR . 'config');
        $this->setInstance('path:public', $this->basePath . DIRECTORY_SEPARATOR . 'public');
        $this->setInstance('path:tmp', $this->basePath . DIRECTORY_SEPARATOR . 'tmp');
        $this->setInstance('path:database', $this->basePath . DIRECTORY_SEPARATOR . 'database');
    }

    /**
     * Set Application class to Container instances
     *
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function registerBaseBindings()
    {
        static::setBaseInstance($this);
        $config = new Config($this, $this->get('path:config'));
        date_default_timezone_set($config->get('app.default_timezone', 'Europe/Paris'));
        $this->setInstance(Application::class, $this);
        $this->setInstance(Container::class, $this);
    }

    /**
     * Set Module from root AppServiceProvider
     *
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    private function setInitModules()
    {
        if (class_exists('App\Providers\AppServiceProvider')) {
            $appProvider = $this->get('App\Providers\AppServiceProvider');
            $this->addModules($appProvider->getModules());
        }
    }

    /**
     * Replace modules
     *
     * @param array $modules
     * @return void
     */
    public function addModules(array $modules): void
    {
        $this->modules = $modules;
    }

    /**
     * Get all modules
     *
     * @return array<string, string>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Get the name of all modules
     *
     * @return array<string, string>
     */
    public function getModulesName(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Get a command
     *
     * @param string $signature
     * @return string|null
     */
    public function getCommand(string $signature): ?string
    {
        return $this->commands[$signature] ?? null;
    }

    /**
     * Call register from all ServiceProvider
     *
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function registerServiceProviders()
    {
        $module = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Providers';
        foreach (scandir($module) as $path) {
            if (in_array($path, ['.', '..'])) {
                continue;
            }
            $namespace = 'MkyCore\\Providers';
            $provider = str_replace('.php', '', $path);
            $provider = "$namespace\\$provider";
            $provider = $this->get($provider);
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
        }
        $modules = $this->modules;
        $providerPath = '';
        foreach ($modules as $module) {
            $module = $this->get($module);
            if ($module instanceof ModuleKernel) {
                $providerPath = $module->getModulePath() . DIRECTORY_SEPARATOR . 'Providers';
            }
            if (is_dir($providerPath)) {
                foreach (scandir($providerPath) as $path) {
                    if (in_array($path, ['.', '..'])) {
                        continue;
                    }
                    $reflectionModule = new ReflectionClass($module);
                    $provider = str_replace('.php', '', $path);
                    $namespace = str_replace($reflectionModule->getShortName(), 'Providers', get_class($module));
                    $provider = "$namespace\\$provider";
                    $provider = $this->get($provider);
                    if (method_exists($provider, 'register')) {
                        $provider->register();
                    }
                }
            }
        }
    }

    /**
     * Load environment file
     *
     * @param string $envFile
     * @return void
     */
    public function loadEnvironment(string $envFile): void
    {
        if (file_exists($envFile)) {
            $dotEnv = new DotEnv($envFile);
            $dotEnv->load();
            $this->environmentFile = $envFile;
        }
    }

    /**
     * Add module
     *
     * @param string $alias
     * @param string $moduleKernel
     * @return void
     * @throws Exception
     */
    public function addModule(string $alias, string $moduleKernel): void
    {
        if ($this->hasModule($alias)) {
            throw new Exception("Alias $alias already set");
        }
        $this->modules[$alias] = $moduleKernel;
    }

    /**
     * Check if module exists
     *
     * @param string $module
     * @return bool
     */
    public function hasModule(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * Get module kernel if exists
     *
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function getModuleKernel(string $module): ?ModuleKernel
    {
        return $this->get($this->getModule($module)) ?? null;
    }

    /**
     * Get module kernel class name
     *
     * @param string $module
     * @return string|null
     */
    public function getModule(string $module): ?string
    {
        return $this->modules[$module] ?? null;
    }

    /**
     * Get entity hydrated by primary key
     *
     * @throws ReflectionException
     */
    public function getInstanceEntity(Entity|string $entity, mixed $primaryKey): Entity
    {
        if (is_string($entity)) {
            $entity = new $entity();
        }
        $manager = $entity->getManager();
        return $manager->find($primaryKey);
    }

    /**
     * Add event
     *
     * @param string $event
     * @param array|string $listeners
     * @param bool $replace
     * @return void
     */
    public function addEvent(string $event, array|string $listeners, bool $replace = false): void
    {
        $listeners = (array)$listeners;
        if(!$replace){
            $this->events[$event] = array_replace_recursive($this->events[$event] ?? [], $listeners);
        }else{
            $this->events[$event] = $listeners;
        }
    }

    /**
     * Get events
     *
     * @return array[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Get listeners
     *
     * @param string $event
     * @return array|null
     */
    public function getListeners(string $event): ?array
    {
        return $this->events[$event] ?? null;
    }

    /**
     * Get listener by action
     *
     * @param string $event
     * @param mixed $action
     * @return string|null
     */
    public function getListenerActions(string $event, mixed $action): ?string
    {
        return $this->events[$event][$action] ?? null;
    }

    /**
     * @return string
     */
    public function getEnvironmentFile(): string
    {
        return $this->environmentFile;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    public function setCurrentRoute(Route $route)
    {
        $this->currentRoute = $route;
    }
}
