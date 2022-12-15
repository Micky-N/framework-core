<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static array all()
 * @method static void set(string $key, mixed $value)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * @method static void remove(string $key)
 * @method static bool clear()
 * @method static string id()
 * @method static bool destroy()
 * @method static bool isStarted()
 * @see \MkyCore\Session
 */
class Session extends Facade
{
    protected static string $accessor = \MkyCore\Session::class;
}