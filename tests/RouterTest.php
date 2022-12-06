<?php

namespace MkyCore\Tests;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use MkyCore\Application;
use MkyCore\Container;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Router\RouteAlreadyExistException;
use MkyCore\Exceptions\Router\RouteNeedParamsException;
use MkyCore\Exceptions\Router\RouteNotFoundException;
use MkyCore\Middlewares\DispatcherMiddleware;
use MkyCore\Middlewares\RouterMiddleware;
use MkyCore\Request;
use MkyCore\Router\Route;
use MkyCore\Router\Router;
use MkyCore\Tests\App\Controllers\TestController;
use MkyCore\Tests\App\Services\PaymentServiceInterface;
use MkyCore\Tests\App\Services\PaypalService;

class RouterTest extends TestCase
{
    public Router $router;

    /**
     * @return void
     * @throws ReflectionException
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . DIRECTORY_SEPARATOR . 'App');
        $this->router = new Router($this->app);
    }

    /**
     * @return void
     * @throws RouteAlreadyExistException
     * @throws RouteNeedParamsException
     * @throws RouteNotFoundException
     */
    public function testNameRoute()
    {
        $route = $this->router->get('boo', function () {
        })->as('booName');
        $this->assertTrue($route->getName() == 'booName');
        $this->assertEquals('/boo', $this->router->getUrlFromName('booName', [], false));
    }

    public function testMatchRoute()
    {
        $route = $this->router->get('boo', function () {
        });
        $this->assertTrue($route->check(new ServerRequest('get', '/boo')));
        $this->assertFalse($route->check(new ServerRequest('get', '/boo2')));
    }

    public function testRunRoute()
    {
        $route = $this->router->get('boo', function () {
            return true;
        });
        $dispatcherMiddleware = new DispatcherMiddleware($this->app);
        $request = new Request('get', '/boo');
        $res = $dispatcherMiddleware->process($request->withAttribute(Route::class, $route), fn() => null);
        $this->assertTrue($res);
        try {
            $request = new Request('get', '/boo2');
            $dispatcherMiddleware->process($request->withAttribute(Route::class, $route), fn() => null);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(RouteNotFoundException::class, $ex);
        }
    }

    public function testRunWithParams()
    {
        $route = $this->router->get('boo/{id}', function ($id) {
        });
        $this->assertTrue($route->check(new ServerRequest('get', '/boo/1')));
        $this->assertFalse($route->check(new ServerRequest('get', '/boo')));
    }

    public function testSlugRoute()
    {
        $request = new Request('get', '/boo/b12/flm');
        $this->router->get('boo/b{id}/f{fa}', [TestController::class, 'multiple']);
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => null);
        };

        $this->assertEquals(['12', 'lm'], $routeMiddleware->process($request, $dispatcherMiddleware));
    }

    public function testRouteToController()
    {
        $request1 = new Request('get', 'boo');
        $request2 = new Request('get', 'boo/12');
        $this->router->get('boo', [TestController::class, 'index']);
        $this->router->get('boo/{id}', [TestController::class, 'show']);
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => null);
        };
        $this->assertEquals('green', $routeMiddleware->process($request1, $dispatcherMiddleware));
        $this->assertEquals('red 12', $routeMiddleware->process($request2, $dispatcherMiddleware));
    }

    public function testRouteOptionalParams()
    {
        $request1 = new Request('get', '/boo/');
        $request2 = new Request('get', '/boo/12');
        $this->router->get('boo/{id?}', [TestController::class, 'optional']);
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => null);
        };

        $this->assertEquals([], $routeMiddleware->process($request1, $dispatcherMiddleware));
        $this->assertEquals(['id' => 12], $routeMiddleware->process($request2, $dispatcherMiddleware));
    }

    public function testPostRouteToController()
    {
        $data = ['name' => 'micky'];
        $request = new Request('post', 'boo');
        $request = $request->withParsedBody($data);
        $this->router->post('boo', [TestController::class, 'post']);
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => null);
        };
        $this->assertEquals('micky', $routeMiddleware->process($request, $dispatcherMiddleware));
    }

    public function testRouteAlreadyExistError()
    {
        try {
            $this->router->get('boo', [TestController::class, 'index']);
            $this->router->get('boo', [TestController::class, 'index']);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(RouteAlreadyExistException::class, $ex);
        }
    }

    public function testRouteNotFoundError()
    {
        $request = new Request('get', 'boo');
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => new RouteNotFoundException("not found"));
        };
        $res = $routeMiddleware->process($request, $dispatcherMiddleware);
        $this->assertInstanceOf(RouteNotFoundException::class, $res);
    }

    public function testRouteGetNeedParamsError()
    {
        try {
            $this->router->get('boo/{id}', function ($id) {
            })->as('boo');
            $this->router->getUrlFromName('boo');
        }catch (\Exception $ex){
            $this->assertInstanceOf(RouteNeedParamsException::class, $ex);
        }
    }

    public function testRouteWithContainer()
    {
        $this->app->bind(PaymentServiceInterface::class, fn() => new PaypalService(1.2, 4));
        $this->router->get('foo/{id}/bar', [TestController::class, 'test']);
        $this->router->get('foo2/{id}/bar', function (PaymentServiceInterface $paymentService, int $id) {
            return $paymentService->getTotal() . '€ pour ' . $id;
        });
        $request1 = new Request('get', 'foo/12/bar');
        $request2 = new Request('get', 'foo2/12/bar');
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => null);
        };
        $this->assertEquals('4.8€ pour 12', $routeMiddleware->process($request1, $dispatcherMiddleware));
        $this->assertEquals('4.8€ pour 12', $routeMiddleware->process($request2, $dispatcherMiddleware));
    }

    public function testRouteWithParamsForContainer()
    {
        $this->app->bind(PaymentServiceInterface::class, fn(Container $container, int $paymentService) => new PaypalService(1.2, $paymentService));
        $this->router->get('foo/{id}/bar/{paymentService}', [TestController::class, 'test']);
        $request = new Request('get', 'foo/12/bar/4');
        $routeMiddleware = new RouterMiddleware($this->app, $this->router);
        $dispatcherMiddleware = function ($request) {
            $dispatcherMiddleware = new DispatcherMiddleware($this->app);
            return $dispatcherMiddleware->process($request, fn() => null);
        };
        $this->assertEquals('4.8€ pour 12', $routeMiddleware->process($request, $dispatcherMiddleware));
    }
}
