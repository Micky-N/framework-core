<?php


namespace MkyCore\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    /**
     * @param callable $next
     * @param ServerRequestInterface $request
     * @return mixed
     */
    public function process(callable $next, ServerRequestInterface $request);
}