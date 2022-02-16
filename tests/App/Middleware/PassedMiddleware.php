<?php


namespace MkyCore\Tests\App\Middleware;


use Psr\Http\Message\ServerRequestInterface;

class PassedMiddleware implements \MkyCore\Interfaces\MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(callable $next, ServerRequestInterface $request)
    {
        return $next($request);
    }
}