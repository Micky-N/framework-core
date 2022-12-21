<?php

namespace MkyCore\Interfaces;

use MkyCore\Request;

interface MiddlewareInterface
{
    /**
     * Run middleware, if request is valid
     * go to next
     *
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function process(Request $request, callable $next): mixed;
}