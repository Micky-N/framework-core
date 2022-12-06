<?php

namespace MkyCore\Middlewares;

use Exception;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Response;
use MkyCore\Router\Route;

class RouteHandlerMiddleware implements MiddlewareInterface
{

    private array $routeMiddlewares = [];

    private int $index = 0;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $app)
    {
        $this->setInitRouteMiddlewares();
    }

    /**
     * @throws ReflectionException
     */
    private function setInitRouteMiddlewares(): void
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
            $namespace = strtolower(str_replace('Module', '', $key));
            $key = $key != 'root' ? $namespace . ':' : '';
            if (is_dir($this->app->get($module))) {
                $aliases = include($this->app->get($module) . '/Middlewares/aliases.php');
                if (!empty($aliases['routeMiddlewares'])) {
                    foreach ($aliases['routeMiddlewares'] as $alias => $middleware) {
                        $this->setRouteMiddleware($key . $alias, $middleware);
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
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        $route = $request->getAttribute(Route::class);
        if($route && $this->routeHasMiddlewares($route)){
            $module = $request->getAttribute('currentModule');
            $this->routeMiddlewares = $this->getRouteMiddlewaresByRoute($route, $module);
            return $this->processRoute($request, $next);
        }
        return $next($request);
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
     * @return MiddlewareInterface[]
     */
    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    /**
     * @param string $key
     * @param string $module
     * @return string|null
     */
    public function getRouteMiddleware(string $key, string $module = ''): ?string
    {
        if($module){
            $module = strtolower(str_replace('Module', '', $module));
            $key = str_replace('@this', $module, $key);
        }
        return $this->routeMiddlewares[$key] ?? null;
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
     * @param Route $route
     * @param string $module
     * @return array|bool
     * @throws Exception
     */
    private function getRouteMiddlewaresByRoute(Route $route, string $module = ''): array|bool
    {
        $routeMiddlewaresAliases = $route->getMiddlewares();
        return array_map(function($routeMiddlewareAlias) use ($module){
            if(!($routeMiddleware = $this->getRouteMiddleware($routeMiddlewareAlias, $module))){
                throw new Exception("No middleware found with the alias $routeMiddlewareAlias");
            }
            return $routeMiddleware;
        }, $routeMiddlewaresAliases);
    }

    /**
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface|array
    {
        if(!$this->routeMiddlewares){
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

    private function routeHasMiddlewares(Route $route): bool
    {
        return !empty($route->getMiddlewares());
    }
}