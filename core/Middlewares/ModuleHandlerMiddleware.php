<?php

namespace MkyCore\Middlewares;

use Exception;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;
use MkyCore\Response;
use MkyCore\Router\Route;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

class ModuleHandlerMiddleware implements MiddlewareInterface
{

    private array $moduleMiddlewares = [];

    private int $index = 0;
    /**
     * @var callable|null
     */
    private $next = null;

    /**
     * @param Application $app
     */
    public function __construct(private readonly Application $app)
    {
    }

    /**
     * Run module middleware
     *
     * @inheritDoc
     * @throws Exception
     */
    public function process(Request $request, callable $next): mixed
    {
        if (!$this->next) {
            $this->next = $next;
        }
        if (!$this->moduleMiddlewares) {
            $this->setMiddlewareFromModuleAliasFile($request);
        }
        if (!empty($this->moduleMiddlewares)) {
            return $this->processModule($request, [$this, 'processModule']);
        }
        return $next($request);
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws Exception
     */
    public function processModule(Request $request, ?callable $next = null): ResponseHandlerInterface
    {
        if ($middleware = $this->getCurrentMiddleware()) {
            return $middleware->process($request, $next);
        }
        return $this->process($request, $this->next);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function setMiddlewareFromModuleAliasFile(Request $request): void
    {
        if (!($route = $request->getAttribute(Route::class))) {
            return;
        }

        if (!($module = $route->getModule())) {
            return;
        }
        $moduleKernel = $this->app->getModuleKernel($module);
        if (!$moduleKernel) {
            throw new Exception("No kernel found for the module $module");
        }

        if (!($moduleKernel instanceof ModuleKernel)) {
            return;
        }
        $modulePath = $moduleKernel->getModulePath();

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
     * @return MiddlewareInterface|false
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): MiddlewareInterface|false
    {
        if ($this->hasModuleMiddleware($this->index)) {
            $moduleMiddleware = $this->getModuleMiddleware($this->index);
            $this->index++;
            return $this->app->get($moduleMiddleware);
        } else {
            return false;
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