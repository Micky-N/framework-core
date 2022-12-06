<?php

namespace MkyCore\Middlewares;

use Exception;
use MkyCore\Exceptions\Router\RouteNotFoundException;
use MkyCore\Facades\Config;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class NotFoundMiddleware implements MiddlewareInterface
{

    /**
     * @throws Exception
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        if(!Config::get('app.debug_mode', false)){
            return new ResponseHandlerNotFound(404 ,[], '', '', "Route '{$request->path()}' not found");
        }
        throw new RouteNotFoundException("Route '{$request->path()}' not found", 404);
    }
}