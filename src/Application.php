<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Router\Route;
use ReflectionException;

class Application extends Container
{

    private array $modules = [];

    private string $basePath;

    private ?Route $currentRoute = null;

    /**
     * @var array<string, array>
     */
    private array $events = [];

    /**
     * @var array<string, string>
     */
    private array $notifications = [];

    /**
     * @var array<string, string>
     */
    private array $notificationSystems = [];
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
        $this->registerBaseBindings();
        $this->setInitModules();
        $this->registerServiceProviders();
        $this->loadEnvironment($basePath . DIRECTORY_SEPARATOR . '.env');
    }

    private function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->setPathsInContainer();
    }

    private function setPathsInContainer()
    {
        $this->setInstance('path:base', $this->basePath);
        $this->setInstance('path:app', $this->basePath . DIRECTORY_SEPARATOR . 'app');
        $this->setInstance('path:config', $this->basePath . DIRECTORY_SEPARATOR . 'config');
        $this->setInstance('path:public', $this->basePath . DIRECTORY_SEPARATOR . 'public');
    }

    /**
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
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    private function setInitModules()
    {
        if (class_exists('App\Providers\AppServiceProvider')) {
            $appProvider = $this->get(\App\Providers\AppServiceProvider::class);
            $appProvider->registerModule();
        }
    }

    /**
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
                    $reflectionModule = new \ReflectionClass($module);
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

    public function addModules(array $modules): void
    {
        $this->modules = $modules;
    }

    /**
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

    public function hasModule(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * @return ModuleKernel[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function getModuleKernel(string $module): ?ModuleKernel
    {
        return $this->get($this->getModule($module)) ?? null;
    }

    public function getModule(string $module): ?string
    {
        return $this->modules[$module] ?? null;
    }

    /**
     * @throws ReflectionException
     */
    public function getInstanceEntity(Entity|string $entity, mixed $primaryKey): \MkyCore\Abstracts\Entity
    {
        if (is_string($entity)) {
            $entity = new $entity();
        }
        $manager = $entity->getManager();
        return $manager->find($primaryKey);
    }

    public function addEvent(string $event, array|string $listeners)
    {
        $listeners = (array)$listeners;
        $this->events[$event] = $listeners;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function getListeners(string $event): ?array
    {
        return $this->events[$event] ?? null;
    }

    public function getListenerActions(string $event, mixed $action): ?string
    {
        return $this->events[$event][$action] ?? null;
    }

    public function getNotification(string $alias): ?string
    {
        return $this->notifications[$alias] ?? null;
    }

    public function addNotificationSystem(string $alias, string $notificationSystem)
    {
        $this->notificationSystems[$alias] = $notificationSystem;
    }

    public function getNotificationSystem(string $alias): ?string
    {
        return $this->notificationSystems[$alias] ?? null;
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
