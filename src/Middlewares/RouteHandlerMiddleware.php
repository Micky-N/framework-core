<?php

namespace MkyCore\Middlewares;

use Exception;
use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Response;
use MkyCore\Router\Route;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

class RouteHandlerMiddleware implements MiddlewareInterface
{

    private array $routeMiddlewares = [];
    private Route $route;
    private int $index = 0;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $app)
    {
        $this->setMiddlewareFromAliasFile();
    }

    /**
     * @throws ReflectionException
     */
    private function setMiddlewareFromAliasFile(): void
    {
        $modules = $this->app->getModules();
        foreach ($modules as $key => $module) {
            $module = $this->app->get($module);
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
        $this->routeMiddlewares[$alias] = $middleware;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process(Request $request, callable $next): mixed
    {
        if($request->getAttribute(Route::class)){
            $this->route = $request->getAttribute(Route::class);
            if ($this->route && $this->routeHasMiddlewares()) {
                $this->routeMiddlewares = $this->getRouteMiddlewaresByRoute();
                return $this->processRoute($request, $next);
            }
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
        return $this->routeMiddlewares[$key] ?? null;
    }

    /**
     * @param Request $request
     * @param callable $next
     * @return ResponseHandlerInterface
     * @throws ReflectionException
     */
    public function processRoute(Request $request, callable $next): ResponseHandlerInterface
    {
        $middleware = $this->getCurrentMiddleware();
        return $middleware->process($request, $next);
    }

    /**
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface|array
    {
        if (!$this->routeMiddlewares) {
            return [];
        }
        if ($this->hasRouteMiddleware($this->index)) {
            $routeMiddleware = $this->getRouteMiddleware($this->index);
            $this->index++;
            return $this->app->get($routeMiddleware);
        } else {
            return (new Response())->withStatus(404);
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