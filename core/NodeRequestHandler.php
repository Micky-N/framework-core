<?php

namespace MkyCore;

use Exception;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Middlewares\CsrfMiddleware;
use MkyCore\Middlewares\DispatcherMiddleware;
use MkyCore\Middlewares\GlobalHandlerMiddleware;
use MkyCore\Middlewares\MethodMiddleware;
use MkyCore\Middlewares\ModuleHandlerMiddleware;
use MkyCore\Middlewares\NotFoundMiddleware;
use MkyCore\Middlewares\PermissionMiddleware;
use MkyCore\Middlewares\ResponseHandlerNotFound;
use MkyCore\Middlewares\RouteHandlerMiddleware;
use MkyCore\Middlewares\RouterMiddleware;
use MkyCore\Middlewares\TrailingSlashMiddleware;
use MkyCore\Middlewares\WhoopsHandlerMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

class NodeRequestHandler implements RequestHandlerInterface
{

    private array $nodeMiddlewares;
    private int $index = 0;

    public function __construct(private readonly Application $app)
    {
        $this->setInitNodeMiddlewares();
    }

    /**
     * Set globals middlewares
     *
     * @return void
     */
    private function setInitNodeMiddlewares(): void
    {
        $this
            ->setMiddleware(GlobalHandlerMiddleware::class)
            ->setMiddleware(RouterMiddleware::class)
            ->setMiddleware(ModuleHandlerMiddleware::class)
            ->setMiddleware(RouteHandlerMiddleware::class)
            ->setMiddleware(PermissionMiddleware::class)
            ->setMiddleware(DispatcherMiddleware::class)
            ->setMiddleware(NotFoundMiddleware::class);
    }

    /**
     * Set middleware
     *
     * @param string $middleware
     * @return $this
     */
    public function setMiddleware(string $middleware): NodeRequestHandler
    {
        $this->nodeMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->process($request);
        $headers = [];
        if (is_array($response) || (is_object($response) && !($response instanceof ResponseHandlerInterface))) {
            $response = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        return Response::getFromHandler($response, $headers);
    }

    /**
     * Run process
     *
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function process(Request $request): mixed
    {
        try {
            $middleware = $this->getCurrentMiddleware();
            return $middleware->process($request, [$this, 'process']);
        } catch (Exception $exception) {
            if (env('APP_ENV', 'local') === 'local') {
                throw $exception;
            }
            return new ResponseHandlerNotFound(status: 500, reason: $exception->getMessage());
        }
    }

    /**
     * Get current middleware
     *
     * @return ResponseInterface|MiddlewareInterface
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface
    {
        if (isset($this->nodeMiddlewares[$this->index])) {
            $middleware = $this->nodeMiddlewares[$this->index];
            $this->index++;
            return $this->app->get($middleware);
        } else {
            return new ResponseHandlerNotFound();
        }
    }
}