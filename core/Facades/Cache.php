<?php

namespace MkyCore\Facades;

use MkyCore\Cache as CoreCache;

/**
 * @method static void addCache(string $file, string $data)
 * @method static void removeCache(string $file)
 *
 * @see \MkyCore\Cache
 */
class Cache
{
    /**
     * @var CoreCache|null
     */
    public static ?CoreCache $cache;

    public static function __callStatic($method, $arguments)
    {
        if (empty(self::$cache)) {
            if (!file_exists(config('cache'))) {
                mkdir(config('cache'), 1);
            }
            self::$cache = new CoreCache();
        }
        return call_user_func_array([self::$cache, $method], $arguments);
    }
}
