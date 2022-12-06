<?php


namespace MkyCore\Tests\App\Middleware;


use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Request;

class ConditionMiddleware implements \MkyCore\Interfaces\MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        if($request->getParsedBody() && !$request->getParsedBody()['go']){
            return false;
        }
        return $next($request);
    }
}