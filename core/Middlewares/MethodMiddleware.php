<?php

namespace MkyCore\Middlewares;

use MkyCore\Facades\Auth;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;
use MkyCore\Router\Router;

class MethodMiddleware implements MiddlewareInterface
{

    /**
     * Retrieve request method and
     * send request with the true method
     *
     * @inheritDoc
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function process(Request $request, callable $next): mixed
    {
        $routeMethods = Router::METHODS;
        if ($request->isMethod(Request::METHOD_POST)
            && ($method = $request->post(Request::METHOD_KEY_FORM))
            && in_array(strtoupper($method), $routeMethods)
        ) {
            $request = $request->withParsedBody($request->except(Request::METHOD_KEY_FORM))
                ->withMethod($method);
        }
        return $next($request);
    }

}