<?php


namespace MkyCore;


use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Middleware\MiddlewareException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Tests\App\Middleware\ResponseHandlerTest;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

class Middleware
{

    private int $index = 0;
    /**
     * @var string[]
     */
    private array $middlewares = [];

    /**
     * Middleware constructor.
     * @param Application $app
     */
    public function __construct(private readonly Application $app)
    {
    }

    /**
     * Construct singleton and run process
     *
     * @param string|string[] $middlewares
     * @return bool
     * @throws FailedToResolveContainerException
     * @throws MiddlewareException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function run(array|string $middlewares): bool
    {
        if (empty($middlewares)) {
            throw new MiddlewareException("Method need middlewares");
        }

        $this->middlewares = (array)$middlewares;
        $res = $this->process($this->app->get(Request::class));
        return $res->getStatusCode() === 200;
    }

    /**
     * handle middlewares
     *
     * @param Request $request
     * @return ResponseInterface
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function process(Request $request): ResponseInterface
    {
        $middleware = $this->getCurrentMiddleware();
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, fn($request) => $this->process($request));
        }
        return $middleware;
    }

    /**
     * @return ResponseInterface|MiddlewareInterface|ResponseHandlerInterface
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ResponseInterface|MiddlewareInterface|ResponseHandlerInterface
    {
        if (isset($this->middlewares[$this->index])) {
            $middleware = $this->middlewares[$this->index];
            $this->index++;
            return $this->app->get($middleware);
        } else {
            return new ResponseHandlerTest(400, ['content-type' => 'text/html'], 'test');
        }
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getMiddleware(string $key): ?string
    {
        return $this->middlewares[$key] ?? null;
    }
}