<?php

namespace MkyCore\Middlewares;

use Exception;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Router\Route;
use ReflectionException;

class RouteHandlerMiddleware implements MiddlewareInterface
{

    private array $routeMiddlewares = [];
    private array $routeNameMiddlewares = [];
    private Route $route;
    private int $index = 0;
    /**
     * @var callable|null
     */
    private $next = null;

    /**
     * @param Application $app
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $app)
    {
        $this->setMiddlewareFromAliasFile();
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function setMiddlewareFromAliasFile(): void
    {
        $modules = $this->app->getModules();
        foreach ($modules as $key => $module) {
            $module = $this->app->getModuleKernel($key);
            $modulePath = $module->getModulePath();
            if (is_dir($modulePath)) {
                $aliases = include($modulePath . '/Middlewares/aliases.php');
                if (!empty($aliases['routeMiddlewares'])) {
                    foreach ($aliases['routeMiddlewares'] as $alias => $middleware) {
                        $this->setRouteMiddleware("$key:" . $alias, $middleware);
                    }
                }
            }
        }
    }

    public function setRouteMiddleware(string $alias, string $middleware): static
    {
        $this->routeNameMiddlewares[$alias] = $middleware;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process(Request $request, callable $next): mixed
    {
        if (!$this->next) {
            $this->next = $next;
        }
        if (!($this->route = $request->getAttribute(Route::class))) {
            return $next($request);
        }
        if (!$this->routeHasMiddlewares()) {
            return $next($request);
        }
        $this->routeMiddlewares = $this->getRouteMiddlewaresByRoute();
        if (isset($this->routeMiddlewares[$this->index])) {
            return $this->processRoute($request, [$this, 'processRoute']);
        }
        return $next($request);
    }

    private function routeHasMiddlewares(): bool
    {
        return !empty($this->route->getMiddlewares());
    }

    /**
     * @return array|bool
     * @throws Exception
     */
    private function getRouteMiddlewaresByRoute(): array|bool
    {
        $routeMiddlewaresAliases = $this->route->getMiddlewares();
        return array_map(function ($routeMiddlewareAlias) {
            if (!($routeMiddleware = $this->getRouteMiddleware($routeMiddlewareAlias))) {
                $routeMiddlewareAlias = str_replace('@:', '', $routeMiddlewareAlias);
                throw new Exception("No middleware found with the alias \"$routeMiddlewareAlias\"");
            }
            return $routeMiddleware;
        }, $routeMiddlewaresAliases);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getRouteMiddleware(string $key): ?string
    {
        if (str_contains($key, '@:')) {
            $key = str_replace('@', $this->route->getModule(), $key);
        }
        return $this->routeNameMiddlewares[$key] ?? null;
    }

    /**
     * @param Request $request
     * @param callable|null $next
     * @return ResponseHandlerInterface
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws Exception
     */
    public function processRoute(Request $request, ?callable $next = null): ResponseHandlerInterface
    {
        if ($middleware = $this->getCurrentMiddleware()) {
            return $middleware->process($request, $next);
        }
        return $this->process($request, $this->next);
    }

    /**
     * @return MiddlewareInterface|false
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): MiddlewareInterface|false
    {
        if ($this->hasRouteMiddleware($this->index)) {
            $routeMiddleware = $this->routeMiddlewares[$this->index];
            $this->index++;
            return $this->app->get($routeMiddleware);
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasRouteMiddleware(string $key): bool
    {
        return isset($this->routeMiddlewares[$key]);
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }
}