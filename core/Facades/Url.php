<?php

namespace MkyCore\Facades;

use MkyCore\Abstracts\Facade;

/**
 * @method static string make(string|array $url, array $query = [])
 * @method static string makeWithParams(string|array $url, array $params, array $query = [])
 * @see \MkyCore\Url
 */
class Url extends Facade
{

    protected static string $accessor = \MkyCore\Url::class;
}