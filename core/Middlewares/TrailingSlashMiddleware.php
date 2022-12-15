<?php

namespace MkyCore\Middlewares;

use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;

class TrailingSlashMiddleware implements MiddlewareInterface
{

    public function process(Request $request, callable $next): mixed
    {
        $url = $request->path();
        if (!empty($url) && strlen($url) > 1 && str_ends_with($url, '/')) {
            $to = substr($url, 0, -1);
            return redirect($to, 301);
        }
        return $next($request);
    }
}
