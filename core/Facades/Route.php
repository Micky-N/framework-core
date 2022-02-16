<?php

namespace MkyCore\Facades;

use MkyCore\Module;
use MkyCore\Router as CoreRouter;


/**
 * @method static \MkyCore\Route get(string $path, $action)
 * @method static \MkyCore\Route post(string $path, $action)
 * @method static array routesByName()
 * @method static \MkyCore\Route[] getRoutes()
 * @method static string generateUrlByName(string $routeName, array $params = [])
 * @method static bool|string currentRoute(string $route = '', bool $path = false)
 * @method static bool isModuleRoute()
 * @method static void run(\Psr\Http\Message\ServerRequestInterface $request)
 * @method static \MkyCore\Router redirectName(string $name)
 * @method static \MkyCore\Router redirect(string $url)
 * @method static \MkyCore\Router withError(array $errors)
 * @method static \MkyCore\Router withSuccess(array $success)
 * @method static \MkyCore\Router with(array $messages)
 * @method static \MkyCore\Router back()
 *
 * @see \MkyCore\Router
 */
class Route{

    /**
     * @var CoreRouter|null
     */
    public static ?CoreRouter $router;

    /**
     * @param $method
     * @param $arguments
     * @return void
     */
    public static function __callStatic($method, $arguments)
    {
        if(empty(self::$router)){
            self::$router = new CoreRouter();
        }
        return call_user_func_array([self::$router, $method], $arguments);
    }
}