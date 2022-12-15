<?php

namespace MkyCore\Facades;

/**
 * @method static string make(string|array $url, array $query = [])
 * @method static string makeWithParams(string|array $url, array $params, array $query = [])
 * @see \MkyCore\Url
 */
class Url extends \MkyCore\Abstracts\Facade
{

    protected static string $accessor = \MkyCore\Url::class;
}