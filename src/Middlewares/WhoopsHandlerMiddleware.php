<?php

namespace MkyCore\Middlewares;

use Middlewares\Utils\RequestHandler;
use Middlewares\Whoops;
use ReflectionException;
use MkyCore\Facades\Config;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

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
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        $this->whoops->process($request, new RequestHandler(fn() => null));
        return $next($request);
    }
}