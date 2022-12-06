<?php

namespace MkyCore\Middlewares;

use ReflectionException;
use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Router\Route;
use MkyCore\Router\Router;

class RouterMiddleware implements MiddlewareInterface
{

    public function __construct(private readonly Application $app, private readonly Router $router)
    {
        
    }

    /**
     * @throws ReflectionException
     */
    public function process(Request $request, callable $next): mixed
    {
        $route = $this->router->match($request);
        if(!$route){
            return $next($request);
        }
        $module = $this->getModuleFromRoute($route);
        $params = $route->getParams();
        $params['currentModule'] = $module;

        $request = array_reduce(array_keys($params), function(Request $request, $key) use ($params){
            return $request->withAttribute($key, $params[$key]);
        }, $request->withAttribute(get_class($route), $route));
        $this->app->singleton(Request::class, fn() => $request);
        return $next($request);
    }

    private function getModuleFromRoute(Route $route): string
    {
        return $route->getModule();
    }
}