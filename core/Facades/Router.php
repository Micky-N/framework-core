<?php

namespace MkyCore\Facades;

use MkyCore\Abstracts\Facade;
use MkyCore\Router\Route;

/**
 * @method static \MkyCore\Router\Route get(string $url, callable|array $action, string $module = '')
 * @method static \MkyCore\Router\Route post(string $url, callable|array $action, string $module = '')
 * @method static \MkyCore\Router\Route put(string $url, callable|array $action, string $module = '')
 * @method static \MkyCore\Router\Route options(string $url, callable|array $action, string $module = '')
 * @method static \MkyCore\Router\Route patch(string $url, callable|array $action, string $module = '')
 * @method static \MkyCore\Router\Route delete(string $url, callable|array $action, string $module = '')
 * @method static \MkyCore\Router\RouteCrud crud(string $namespace, string $controller, string $moduleName = '')
 * @method static \MkyCore\Router\RouteCrud crudApi(string $namespace, string $controller, string $moduleName = '')
 * @method static \MkyCore\Router\Route getCurrentRoute()
 * @method static array getRoutes(array $filters = [])
 * @method static void deleteRoute(Route $route)
 * @method static string getUrlFromName(string $name, array $params = [], bool $absolute = true)
 * @see \MkyCore\Router\Router
 */
class Router extends Facade
{
    protected static string $accessor = \MkyCore\Router\Router::class;
}