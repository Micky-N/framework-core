<?php

namespace MkyCore\Facades;

use DateTime;
use MkyCore\Abstracts\Facade;

/**
 * @method static bool set(string $id, string|array $value, int|DateTime|string $time = 3600, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = true)
 * @method static mixed get(string $id, mixed $default = null)
 * @method static mixed pull(string $id, mixed $default = null)
 * @method static bool remove(string $id)
 * @see \MkyCore\Cookie
 */
class Cookie extends Facade
{
    protected static string $accessor = \MkyCore\Cookie::class;
}