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

class ModuleHandlerMiddleware implements MiddlewareInterface
{

    private array $moduleMiddlewares = [];

    private int $index = 0;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Application $app)
    {
        $this->setInitModuleMiddlewares();
    }

    /**
     * @throws ReflectionException
     */
    private function setInitModuleMiddlewares(): void
    {
        $this->setMiddlewareFromModuleAliasFile();
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function setMiddlewareFromModuleAliasFile(): void
    {
        $module = app()->get(Request::class)->getAttribute('currentModule');
        if(!$module){
            return;
        }
        $modulePath = $this->app->getModulePath($module);
        if(!$modulePath){
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
        if(!empty($this->moduleMiddlewares)){
            $middleware = $this->getCurrentMiddleware();
            return $middleware->process($request, $next);
        }
        return $next($request);
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getModuleMiddlewares(): array
    {
        return $this->moduleMiddlewares;
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
     * @param string $key
     * @return bool
     */
    public function hasModuleMiddleware(string $key): bool
    {
        return isset($this->moduleMiddlewares[$key]);
    }

    /**
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface|array
    {
        if(!$this->moduleMiddlewares){
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
}