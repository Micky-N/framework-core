<?php


namespace MkyCore\Tests\App\Middleware;


use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class PassedMiddleware implements MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        return $next($request);
    }
}