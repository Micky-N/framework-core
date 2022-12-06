<?php

namespace MkyCore\Interfaces;

use MkyCore\Request;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function process(Request $request, callable $next): mixed;
}