<?php

namespace MkyCore\Facades;

/**
 * @method static bool run(array|string $middlewares)
 * @see \MkyCore\Middleware
 */
class Middleware extends \MkyCore\Abstracts\Facade
{
    protected static string $accessor = \MkyCore\Middleware::class;
}