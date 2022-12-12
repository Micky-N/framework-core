<?php

namespace MkyCore\Middlewares;

use MkyCore\Application;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;

class MethodMiddleware implements MiddlewareInterface
{

    public function __construct(private readonly Application $app)
    {

    }

    public function process(Request $request, callable $next): mixed
    {
        if ($request->isMethod(Request::METHOD_POST) && ($method = $request->post('_method'))) {
            $request = $request->withParsedBody($request->except($request::METHOD_KEY_FORM))
                ->withMethod($method);
        }
        return $next($request);
    }

}