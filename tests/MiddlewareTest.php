<?php

namespace MkyCore\Tests;


use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use MkyCore\Application;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Router\RouteMiddlewareException;
use MkyCore\Middleware;
use MkyCore\Router\Router;
use MkyCore\Tests\App\Middleware\BlockedMiddleware;
use MkyCore\Tests\App\Middleware\PassedMiddleware;
use MkyCore\Tests\App\Middleware\TestMiddleware;

class MiddlewareTest extends TestCase
{
    /**
     * @var Router
     */
    private Router $router;
    private Application $app;
    private Middleware $middleware;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function setUp(): void
    {
        $this->app = new Application(__DIR__.DIRECTORY_SEPARATOR.'App');
        $this->router = new Router($this->app);
        $this->middleware = new Middleware($this->app);
    }

    public function testMultipleMiddleware()
    {
        $this->assertFalse($this->middleware->run(PassedMiddleware::class));
        $this->assertEquals(1, $this->middleware->getIndex());
        $this->assertFalse($this->middleware->run([PassedMiddleware::class, TestMiddleware::class]));
        $this->assertEquals(2, $this->middleware->getIndex());
    }

    public function testPriorityAndStopMiddleware()
    {
        $this->assertFalse($this->middleware->run([PassedMiddleware::class, BlockedMiddleware::class, TestMiddleware::class]));
        // GET THE LAST RUNNING MIDDLEWARE
        $this->assertEquals(BlockedMiddleware::class, $this->middleware->getMiddleware($this->middleware->getIndex() - 1));
        $this->assertEquals(2, $this->middleware->getIndex());
    }
}
