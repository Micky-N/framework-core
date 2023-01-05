<?php

namespace MkyCore\Middlewares;

use Middlewares\Utils\RequestHandler;
use Middlewares\Whoops;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;
use ReflectionException;

class WhoopsHandlerMiddleware implements MiddlewareInterface
{

    private Whoops $whoops;


    public function __construct()
    {
        $this->whoops = new Whoops();
    }

    /**
     * @throws ReflectionException
     */
    public function process(Request $request, callable $next): mixed
    {
        $this->whoops->process($request, new RequestHandler(fn() => null));
        if (env('APP_ENV', 'local') === 'local') {
            $this->whoops->catchErrors();
        }
        return $next($request);
    }
}