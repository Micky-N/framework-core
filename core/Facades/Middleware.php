<?php

namespace MkyCore\Facades;

use MkyCore\Abstracts\Facade;

/**
 * @method static bool run(array|string $middlewares)
 * @see \MkyCore\Middleware
 */
class Middleware extends Facade
{
    protected static string $accessor = \MkyCore\Middleware::class;
}