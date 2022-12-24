<?php

namespace MkyCore\Middlewares;


use Closure;
use MkyCore\Abstracts\Entity;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Router\Route;
use ReflectionException;
use ReflectionUnionType;

class DispatcherMiddleware implements MiddlewareInterface
{

    public function __construct(private readonly Application $app)
    {
    }

    /**
     * Run middleware
     * Find controller and method and call them
     * with passed params
     *
     * @inheritDoc
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
        $requestAttributes = $request->getAttributes();
        if (isset($requestAttributes[Route::class])) {
            $route = $requestAttributes[Route::class];
            unset($requestAttributes[Route::class]);
            $actionRoute = $route->getAction();
            $methodReflection = null;
            if (is_array($actionRoute)) {
                $controller = $actionRoute[0];
                $method = $actionRoute[1];
                $controller = $this->app->get($controller);
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
                $paramType = $reflectionParameter->getType();
                if ($paramType instanceof ReflectionUnionType) {
                    $index = 0;
                    $paramTypes = $paramType->getTypes();
                    if (isset($requestAttributes[$name])) {
                        $type = gettype($requestAttributes[$name]);
                        for ($t = 0; $t < count($paramTypes); $t++) {
                            $paramT = $paramTypes[$t];
                            if ($paramT->getName() == $type) {
                                $index = $t;
                                break;
                            }
                        }
                    }
                    $paramType = $paramTypes[$index];
                }

                if ($paramType && !$paramType->isBuiltin()) {
                    $param = isset($requestAttributes[$name]) ? [$name => $requestAttributes[$name]] : [];
                    $class = $paramType->getName();
                    if (is_string($class)) {
                        if (class_exists($class)) {
                            $class = $this->app->get($class);
                            if ($class instanceof Entity) {
                                $param[$name] = $this->app->getInstanceEntity($class, $param[$name]);
                            }
                        } elseif (interface_exists($class)) {
                            $param[$name] = $this->app->get($class, $param[$name] ?? []);
                        }
                    }
                    $params[$name] = $param[$name] ?? $this->app->get($paramType->getName(), $param);
                } elseif ($paramType && $paramType->isBuiltin() && !empty($requestAttributes[$name])) {
                    $params[$name] = $requestAttributes[$name];
                } elseif (!$paramType && !empty($requestAttributes[$name])) {
                    $params[$name] = (string)$requestAttributes[$name];
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