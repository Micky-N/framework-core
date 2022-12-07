<?php

namespace MkyCore\Middlewares;


use Closure;
use ReflectionException;
use MkyCore\Abstracts\Entity;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Router\Route;

class DispatcherMiddleware implements MiddlewareInterface
{

    public function __construct(private readonly Application $app)
    {
    }

    /**
     * @param Request $request
     * @param callable $next
     * @return ResponseHandlerInterface
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function process(Request $request, callable $next): mixed
    {
        $this->app->forceSingleton(Request::class, $request);
        $routeParams = $request->getAttributes();
        if (isset($routeParams[Route::class])) {
            $route = $routeParams[Route::class];
            unset($routeParams[Route::class]);
            $actionRoute = $route->getAction();
            $methodReflection = null;
            if (is_array($actionRoute)) {
                $controller = $actionRoute[0];
                $method = $actionRoute[1];
                if (is_string($controller)) {
                    $controller = $this->app->get($controller);
                }

                $controllerReflection = new \ReflectionClass($controller);
                $methodReflection = $controllerReflection->getMethod($method);
            } elseif ($actionRoute instanceof Closure) {
                $methodReflection = new \ReflectionFunction($actionRoute);
            }
            $reflectionParameters = $methodReflection->getParameters();
            $params = [];
            for ($i = 0; $i < count($reflectionParameters); $i++) {
                $reflectionParameter = $reflectionParameters[$i];
                $name = $reflectionParameter->getName();
                if ($reflectionParameter->getType() && !$reflectionParameter->getType()->isBuiltin()) {
                    $param = isset($routeParams[$name]) ? [$name => $routeParams[$name]] : [];
                    $class = $reflectionParameter->getType()->getName();
                    if (class_exists($class) && is_string($class)) {
                        $class = $this->app->get($class);
                        if ($class instanceof Entity) {
                            $param[$name] = $this->app->getInstanceEntity($class, $param[$name]);
                        }
                    } elseif (interface_exists($class) && is_string($class)) {
                        $param[$name] = $this->app->get($class, $param[$name] ?? []);
                    }
                    $params[$name] = $param[$name] ?? $this->app->get($reflectionParameter->getType()->getName(), $param);
                } elseif ($reflectionParameter->getType() && $reflectionParameter->getType()->isBuiltin() && !empty($routeParams[$name])) {
                    $params[$name] = $routeParams[$name];
                } elseif (!$reflectionParameter->getType() && !empty($routeParams[$name])) {
                    $params[$name] = (string)$routeParams[$name];
                } elseif ($route->isOptionalParam($name)) {
                    $params[$name] = $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null;
                } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                    $params[$name] = $reflectionParameter->getDefaultValue();
                }
            }
            if (is_array($actionRoute)) {
                return $methodReflection->invokeArgs($controller, $params);
            } elseif ($actionRoute instanceof Closure) {
                return $methodReflection->invokeArgs($params);
            }
        }
        return $next($request);
    }

}