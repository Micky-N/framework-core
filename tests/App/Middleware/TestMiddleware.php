<?php


namespace MkyCore\Tests\App\Middleware;


use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class TestMiddleware implements \MkyCore\Interfaces\MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        return $next($request);
    }
}