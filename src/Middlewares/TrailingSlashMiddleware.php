<?php

namespace MkyCore\Middlewares;

use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class TrailingSlashMiddleware implements MiddlewareInterface
{

    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        $url = $request->getUri()->getPath();
        if (!empty($url) && str_ends_with($url, '/')) {
            $to = substr($url, 0, -1);
            return redirect($to, 301);
        }
        return $next($request);
    }
}
