<?php

namespace MkyCore\Middlewares;

use Exception;
use MkyCore\Exceptions\Router\RouteNotFoundException;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\JsonResponse;
use MkyCore\Request;

class NotFoundMiddleware implements MiddlewareInterface
{

    /**
     * @throws Exception
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        if ($this->isApi($request)) {
            return json_response([
                'error' => "Route '{$request->path()}' not found"
            ], 404);
        }
        if (env('APP_ENV', 'local') !== 'local') {
            return new ResponseHandlerNotFound(404, [], '', '', "Route '{$request->path()}' not found");
        }
        throw new RouteNotFoundException("Route '{$request->path()}' not found", 404);
    }

    private function isApi(Request $request): bool
    {
        $origin = $request->header('Origin');
        $origin = array_shift($origin);
        $host = $request->header('Host');
        $host = array_shift($host);
        if (!$origin) {
            return true;
        }
        $origin = explode('//', $origin);
        $origin = $origin[1];
        return $host !== $origin;
    }
}