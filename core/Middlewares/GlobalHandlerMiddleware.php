<?php

namespace MkyCore\Middlewares;

use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use ReflectionException;

class GlobalHandlerMiddleware implements MiddlewareInterface
{

    private array $globalMiddlewares = [];
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
        $this->setGlobalMiddlewareFromAliasFile();
    }

    /**
     * Set global middleware from alias file
     * app/Middlewares/aliases.php
     *
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
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

    /**
     * Set global middleware into the instance
     * @param string $middleware
     * @return $this
     */
    public function setGlobalMiddleware(string $middleware): static
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @inheritDoc
     * @param Request $request
     * @param callable $next
     * @return mixed
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function process(Request $request, callable $next): mixed
    {
        if (!$this->next) {
            $this->next = $next;
        }
        if (isset($this->globalMiddlewares[$this->index])) {
            return $this->processGlobal($request, [$this, 'processGlobal']);
        }
        return $next($request);
    }

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    public function processGlobal(Request $request, ?callable $next = null): ResponseHandlerInterface
    {
        if ($middleware = $this->getCurrentMiddleware()) {
            return $middleware->process($request, $next);
        }
        return $this->process($request, $this->next);
    }

    /**
     * @return false|MiddlewareInterface
     */
    private function getCurrentMiddleware(): false|MiddlewareInterface
    {
        if (isset($this->globalMiddlewares[$this->index])) {
            $middleware = $this->globalMiddlewares[$this->index];
            $this->index++;
            try {
                return $this->app->get($middleware);
            } catch (FailedToResolveContainerException|NotInstantiableContainerException|ReflectionException $e) {
                return false;
            }
        } else {
            return false;
        }
    }
}