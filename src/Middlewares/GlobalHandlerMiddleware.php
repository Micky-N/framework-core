<?php

namespace MkyCore\Middlewares;

use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Response;

class GlobalHandlerMiddleware implements MiddlewareInterface
{

    private array $globalMiddlewares = [];
    private int $index = 0;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $app)
    {
        $this->setGlobalMiddlewareFromAliasFile();
    }

    /**
     * @throws ReflectionException
     */
    private function setGlobalMiddlewareFromAliasFile(): void
    {
        $aliases = include($this->app->get('path:app') . '/Middlewares/aliases.php');
        if (!empty($aliases['globalMiddlewares'])) {
            foreach ($aliases['globalMiddlewares'] as $middleware) {
                $this->setGlobalMiddleware($middleware);
            }
        }
    }

    public function setGlobalMiddleware(string $middleware): static
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        if(!empty($this->globalMiddlewares)){
            $middleware = $this->getCurrentMiddleware();
            return $middleware->process($request, $next);
        }
        return $next($request);
    }

    /**
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface
    {
        if (isset($this->globalMiddlewares[$this->index])) {
            $middleware = $this->globalMiddlewares[$this->index];
            $this->index++;
            return $this->app->get($middleware);
        } else {
            return (new Response())->withStatus(404);
        }
    }
}