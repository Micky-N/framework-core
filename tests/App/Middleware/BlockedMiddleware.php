<?php


namespace MkyCore\Tests\App\Middleware;


use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class BlockedMiddleware implements \MkyCore\Interfaces\MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        return new ResponseHandlerTest(400);
    }
}