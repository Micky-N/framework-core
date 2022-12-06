<?php

namespace MkyCore;

use ReflectionException;
use MkyCore\Abstracts\Entity;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\ListenerInterface;

class Application extends Container
{

    private array $modules = [];

    private string $basePath;

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
    public function __construct(string $basePath = ROOT_APP)
    {
        $this->setBasePath($basePath);
        $this->registerBaseBindings();
        $this->setInitModules();
        $this->registerServiceProviders();
        $this->loadEnvironment($basePath.DIRECTORY_SEPARATOR.'.env');
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
        $config = new Config($this->get('path:config'));
        date_default_timezone_set($config->get('app.default_timezone', 'Europe/Paris'));
        $this->setInstance(Application::class, $this);
        $this->setInstance(Container::class, $this);
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function setInitModules(): void
    {
        $root = $this->get('path:app');
        foreach (glob($root . '/*Module', GLOB_ONLYDIR) as $dir) {
            $moduleName = trim(str_replace($root, '', $dir), DIRECTORY_SEPARATOR . '/');
            $this->modules[$moduleName] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($dir, DIRECTORY_SEPARATOR . '/'));
        }
        $this->modules['Root'] = $root;
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function registerServiceProviders()
    {
        $modules = $this->modules;
        $modules['src'] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
        foreach ($modules as $module) {
            $providerPath = $this->get($module) . DIRECTORY_SEPARATOR . 'Providers';
            if (is_dir($providerPath)) {
                foreach (scandir($providerPath) as $path) {
                    if (in_array($path, ['.', '..'])) {
                        continue;
                    }
                    $fullPath = $providerPath . DIRECTORY_SEPARATOR . $path;
                    $provider = ucfirst(trim(str_replace([dirname(__DIR__).DIRECTORY_SEPARATOR.'src', $this->basePath, '.php', DIRECTORY_SEPARATOR], ['MkyCore', '', '', '\\'], $fullPath), DIRECTORY_SEPARATOR));
                    $provider = $this->get($provider);
                    $provider->register();
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    public function getModulePath(string $module): ?string
    {
        return $this->modules[$module] ?? null;
    }

    /**
     * @throws ReflectionException
     */
    public function getInstanceEntity(Entity|string $entity, mixed $primaryKey): \MkyCore\Abstracts\Entity
    {
        if(is_string($entity)){
            $entity = new $entity();
        }
        $manager = $entity->getManager();
        return $manager->find($primaryKey);
    }

    public function addEvent(string $event, array|string $listeners)
    {
        $listeners = (array) $listeners;
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
     * @param string $envFile
     * @return void
     */
    public function loadEnvironment(string $envFile): void
    {
        if(file_exists($envFile)){
            $dotEnv = new DotEnv($envFile);
            $dotEnv->load();
            $this->environmentFile = $envFile;
        }
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
}
