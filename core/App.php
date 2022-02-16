<?php

namespace MkyCore;

use MkyCore\Facades\Permission;
use MkyCore\Facades\Route;
use MkyCore\Interfaces\EventInterface;
use MkyCore\Interfaces\ListenerInterface;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\VoterInterface;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;

class App
{
    /**
     * @var string[]
     */
    private static array $modules = [];
    private static ?Module $currentModule = null;
    private static array $middlewareServiceProviders = [];

    /**
     * @var EventInterface[]
     */
    private static array $events = [];
    private static array $providers = [];

    /**
     * @var Route[]
     */
    private static array $routes = [];
    /**
     * @var mixed
     */
    private static $config;
    /**
     * @var mixed
     */
    private static $mkyServiceProvider;

    /**
     * Get Provider list
     *
     * @return mixed
     */
    public static function Providers()
    {
        self::$providers = include dirname(__DIR__) . '/app/Providers/Provider.php';
        return self::$providers;
    }

    /**
     * Get events and listeners list
     *
     * @return array|EventInterface[]|mixed
     */
    public static function EventServiceProviders()
    {
        self::$events = include dirname(__DIR__) . '/app/Providers/EventServiceProvider.php';
        return self::$events;
    }

    /**
     * Get middlewares, routeMiddlewares and voters list
     *
     * @return array|mixed
     */
    public static function MiddlewareServiceProviders()
    {
        self::$middlewareServiceProviders = include dirname(__DIR__) . '/app/Providers/MiddlewareServiceProvider.php';
        return self::$middlewareServiceProviders;
    }

    /**
     * Get Module list
     */
    public static function ModuleServiceProvider()
    {
        $moduleServiceProvider = include ROOT.'/app/Providers/ModuleServiceProvider.php';
        self::$modules = $moduleServiceProvider;
    }

    /**
     * Get mkyFormatter and mkyDirective list
     */
    public static function MkyServiceProvider()
    {
        self::$mkyServiceProvider = include dirname(__DIR__) . '/app/Providers/MkyServiceProvider.php';
        return self::$mkyServiceProvider;
    }

    /**
     * Add voters list to Permission
     */
    public static function VotersInit()
    {
        if(config('structure') === 'HMVC'){
            foreach (self::$modules as $module) {
                $moduleRoot = (new $module())->getRoot();
                $moduleVoters = include $moduleRoot . '/Providers/MiddlewareServiceProvider.php';
                self::$middlewareServiceProviders['voters'] = array_merge(self::$middlewareServiceProviders['voters'], $moduleVoters['voters']);
            }
        }
        foreach (self::$middlewareServiceProviders['voters'] as $voter) {
            Permission::addVoter(new $voter());
        }
    }

    /**
     * Add event and listener
     *
     * @param string $event
     * @param string $key
     * @param string $class
     */
    public static function setEvents(string $event, string $key, string $class)
    {
        self::$events[$event][$key] = $class;
    }

    /**
     * Add alias and class to Provider
     *
     * @param string $key
     * @param string $class
     */
    public static function setAlias(string $key, string $class)
    {
        self::$providers['alias'][$key] = $class;
    }

    /**
     * Set routeMiddleware
     *
     * @param string $alias
     * @param string $routeMiddleware
     * @return void
     */
    public static function setRouteMiddleware(string $alias, string $routeMiddleware)
    {
        self::$middlewareServiceProviders['routeMiddlewares'][$alias] = $routeMiddleware;
    }

    /**
     * Get all route file in routes folder
     */
    public static function RoutesInit()
    {
        foreach (glob(dirname(__DIR__) . '/routes/*.yaml') as $filename) {
            Route::parseRoutes(Yaml::parseFile($filename) ?? [], null, strpos($filename, 'admin.yaml') !== false);
        }
        if(config('structure') === 'HMVC'){
            $modules = !empty(self::$modules) ? self::$modules : (file_exists(dirname(__DIR__).'/app/Providers/ModuleServiceProvider.php') ? include(dirname(__DIR__).'/app/Providers/ModuleServiceProvider.php') : []);
            foreach ($modules as $module) {
                $currentModule = new $module();
                foreach (glob($currentModule->getRoot() . '/routes/*.yaml') as $filename) {
                    Route::parseRoutes(Yaml::parseFile($filename) ?? [], $currentModule, strpos($filename, 'admin.yaml') !== false);
                }
            }
        }
        self::$routes = Route::getRoutes();
    }

    /**
     * Run the application
     *
     * @param ServerRequestInterface $request
     * @return View
     * @throws Exception
     */
    public static function run(ServerRequestInterface $request)
    {
        self::ConfigInit();
        self::Providers();
        self::ModuleServiceProvider();
        self::MiddlewareServiceProviders();
        self::EventServiceProviders();
        self::MkyServiceProvider();
        self::VotersInit();
        self::RoutesInit();
        try {
            Route::run($request);
        } catch (Exception $ex) {
            return ErrorController::error($ex->getCode(), $ex->getMessage());
        }
        self::debugMode();
    }

    /**
     * Get voters
     *
     * @return VoterInterface[]
     */
    public static function getVoters(): array
    {
        return self::$middlewareServiceProviders['voters'];
    }

    /**
     * Get all middlewares
     * or specific middleware
     *
     * @param string|null $middleware
     * @return MiddlewareInterface[]|MiddlewareInterface|null
     */
    public static function getMiddlewares(string $middleware = null)
    {
        if($middleware){
            return self::$middlewareServiceProviders['middlewares'][$middleware] ?? null;
        }
        return self::$middlewareServiceProviders['middlewares'];
    }

    /**
     * Get all routeMiddlewares
     * or specific routeMiddleware
     *
     * @param string|null $routeMiddleware
     * @return MiddlewareInterface[]|MiddlewareInterface|null
     */
    public static function getRouteMiddlewares(string $routeMiddleware = null)
    {
        if($routeMiddleware){
            return self::$middlewareServiceProviders['routeMiddlewares'][$routeMiddleware] ?? null;
        }
        return self::$middlewareServiceProviders['middlewares'];
    }

    /**
     * Get all events
     *
     * @return EventInterface[]|null
     */
    public static function getEvents()
    {
        return self::$events ?? null;
    }

    /**
     * Get event listeners
     *
     * @param string $event
     * @return ListenerInterface[]|null
     */
    public static function getListeners(string $event)
    {
        return self::$events[$event] ?? null;
    }

    /**
     * Get event listener on action
     *
     * @param string $event
     * @param string $action
     * @return ListenerInterface|null
     */
    public static function getListenerActions(string $event, string $action)
    {
        if(isset(self::$events[$event]) && isset(self::$events[$event][$action])){
            return self::$events[$event][$action];
        }
        return null;
    }

    /**
     * Get class by alias
     *
     * @param string $key
     * @return mixed|null
     */
    public static function getAlias(string $key)
    {
        return self::$providers['alias'][$key] ?? null;
    }

    /**
     * Run debugBar if active
     *
     * @throws Exception
     */
    public static function debugMode()
    {
        if(config('debugMode')){
            echo \MkyCore\Facades\StandardDebugBar::render();
        }
    }

    /**
     * Get all providers
     *
     * @return array
     */
    public static function getProviders(): array
    {
        return self::$providers;
    }

    /**
     * Get all routes
     *
     * @return Route[]
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * @return Module|null
     */
    public static function getCurrentModule()
    {
        return self::$currentModule ?? null;
    }

    /**
     * @param Module $currentModule
     */
    public static function setCurrentModule(Module $currentModule): void
    {
        self::$currentModule = $currentModule;
    }

    /**
     *  Merge current module application to base application
     */
    public static function setApplication()
    {
        $moduleRoot = (new self::$currentModule())->getRoot();
        self::$config['module'] = self::includeMerge(self::$config['module'], self::$currentModule::CONFIG);
        self::$events = self::includeMerge(self::$events, $moduleRoot . '/Providers/EventServiceProvider.php');
    }


    private static function includeMerge($baseArray, $file)
    {
        $arrayFile = include $file;
        return array_merge($baseArray, array_filter($arrayFile));
    }

    public static function ConfigInit()
    {
        foreach (glob(ROOT . '/config/*.php') as $filename) {
            $configFile = trim(str_replace(ROOT . '/config', '', $filename), '/');
            $configFile = str_replace('.php', '', $configFile);
            self::$config[$configFile] = include $filename;
        }
    }

    /**
     * @return array|null
     */
    public static function getConfig()
    {
        return self::$config ?? null;
    }

    /**
     * @param string $key
     * @param mixed $config
     * @return App
     */
    public static function setConfig(string $key, array $config): App
    {
        $newConfig[$key] = $config;
        self::$config = array_merge(self::$config ?? [], $newConfig);
        return new static;
    }
}
