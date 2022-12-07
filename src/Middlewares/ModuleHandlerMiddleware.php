<?php

namespace MkyCore\Middlewares;

use Exception;
use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;
use MkyCore\Response;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

class ModuleHandlerMiddleware implements MiddlewareInterface
{

    private array $moduleMiddlewares = [];

    private int $index = 0;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $app)
    {
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function setMiddlewareFromModuleAliasFile(Request $request): void
    {
        $module = $request->getAttribute('currentModule');
        if (!$module) {
            return;
        }
        $modulePath = $this->app->getModulePath($module);
        if (!$modulePath) {
            throw new Exception("No path found for the module $module");
        }
        $aliases = include($modulePath . '/Middlewares/aliases.php');
        if (!empty($aliases['middlewares'])) {
            foreach ($aliases['middlewares'] as $middleware) {
                $this->setModuleMiddleware($middleware);
            }
        }
    }

    public function setModuleMiddleware(string $middleware): static
    {
        $this->moduleMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process(Request $request, callable $next): mixed
    {
        if(!$this->moduleMiddlewares){
            $this->setMiddlewareFromModuleAliasFile($request);
        }
        if (!empty($this->moduleMiddlewares)) {
            $middleware = $this->getCurrentMiddleware();
            return $middleware->process($request, $next);
        }
        return $next($request);
    }

    /**
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface|array
    {
        if (!$this->moduleMiddlewares) {
            return [];
        }
        if ($this->hasModuleMiddleware($this->index)) {
            $moduleMiddleware = $this->getModuleMiddleware($this->index);
            $this->index++;
            return $this->app->get($moduleMiddleware);
        } else {
            return (new Response())->withStatus(404);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasModuleMiddleware(string $key): bool
    {
        return isset($this->moduleMiddlewares[$key]);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getModuleMiddleware(string $key): ?string
    {
        return $this->moduleMiddlewares[$key] ?? null;
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getModuleMiddlewares(): array
    {
        return $this->moduleMiddlewares;
    }
}