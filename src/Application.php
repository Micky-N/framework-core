<?php

namespace MkyCore;

use App\Providers\AppServiceProvider;
use MkyCore\Abstracts\ModuleKernel;
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

    private function setInitModules()
    {
        $appProvider = $this->get(AppServiceProvider::class);
        $appProvider->registerModule();
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

    public function addModules(array $modules): void
    {
        $this->modules = $modules;
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function registerServiceProviders()
    {
        $module = dirname(__FILE__).DIRECTORY_SEPARATOR.'Providers';
        foreach (scandir($module) as $path) {
            if (in_array($path, ['.', '..'])) {
                continue;
            }
            $namespace = 'MkyCore\\Providers';
            $provider = str_replace('.php', '', $path);
            $provider = "$namespace\\$provider";
            $provider = $this->get($provider);
            if(method_exists($provider, 'register')){
                $provider->register();
            }
        }
        $modules = $this->modules;
        foreach ($modules as $module) {
            $module = $this->get($module);
            if($module instanceof ModuleKernel){
                $providerPath = $module->getModulePath().DIRECTORY_SEPARATOR .'Providers';
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
                    if(method_exists($provider, 'register')){
                        $provider->register();
                    }
                }
            }
        }
    }

    /**
     * @return ModuleKernel[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    public function getModule(string $module): ?string
    {
        return $this->modules[$module] ?? null;
    }
    
    public function getModuleKernel(string $module): ?ModuleKernel
    {
        return $this->get($this->getModule($module)) ?? null;
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
