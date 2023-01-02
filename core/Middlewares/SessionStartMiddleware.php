<?php

namespace MkyCore\Middlewares;

use MkyCore\Application;
use MkyCore\Facades\Auth;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;
use MkyCore\Session;

class SessionStartMiddleware implements MiddlewareInterface
{

    public function __construct(protected readonly Application $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $next): mixed
    {
        if (empty($_SESSION) && session_status() === PHP_SESSION_NONE && empty(session_id())) {
            session_start(config('session.options', []));
        }
        return $next($request);
    }
}