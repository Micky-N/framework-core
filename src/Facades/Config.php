<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @see \MkyCore\Config
 */
class Config extends Facade
{
    protected static string $accessor = \MkyCore\Config::class;
}