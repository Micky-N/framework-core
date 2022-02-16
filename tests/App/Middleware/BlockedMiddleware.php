<?php


namespace MkyCore\Tests\App\Middleware;


use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class BlockedMiddleware implements \MkyCore\Interfaces\MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(callable $next, ServerRequestInterface $request)
    {
        return false;
    }
}