<?php

namespace MkyCore\Facades;

use Closure;
use MkyCore\Abstracts\Facade;
use MkyCore\Router\Route;
use MkyCore\Router\RouteCrud;

/**
 * @method static Route get(string $url, Closure|array|string $action, string $module = '')
 * @method static Route post(string $url, Closure|array|string $action, string $module = '')
 * @method static Route put(string $url, Closure|array|string $action, string $module = '')
 * @method static Route options(string $url, Closure|array|string $action, string $module = '')
 * @method static Route patch(string $url, Closure|array|string $action, string $module = '')
 * @method static Route method(string $url, array $methods, Closure|array|string $action, string $module = '')
 * @method static Route delete(string $url, Closure|array|string $action, string $module = '')
 * @method static Route all(string $url, Closure|array|string $action, string $module = '')
 * @method static RouteCrud crud(string $namespace, string $controller, string $moduleName = '')
 * @method static Route getCurrentRoute()
 * @method static array getRoutes(array $filters = [])
 * @method static void deleteRoute(Route $route)
 * @method static string getUrlFromName(string $name, array $params = [], bool $absolute = true)
 * @see \MkyCore\Router\Router
 */
class Router extends Facade
{
    protected static string $accessor = \MkyCore\Router\Router::class;
}