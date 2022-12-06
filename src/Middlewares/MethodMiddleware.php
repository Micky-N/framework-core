<?php

namespace MkyCore\Middlewares;

use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class MethodMiddleware implements MiddlewareInterface
{

    public function __construct(private readonly Application $app)
    {

    }

    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        if($request->isMethod(Request::METHOD_POST) && ($method = $request->post('_method'))){
            $request = $request->withMethod($method);
            $this->app->singleton(Request::class, fn() => $request);
        }
        return $next($request);
    }

}