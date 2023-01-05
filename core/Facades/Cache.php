<?php

namespace MkyCore\Facades;

use MkyCore\Abstracts\Facade;

/**
 * @method static bool has(string $key)
 * @method static string getCacheDir()
 * @method static string getCacheFile()
 * @method static string getCache()
 * @method static mixed get(string $key, bool $timestamp = false)
 * @method static mixed isExpired(string $key)
 * @method static mixed remove(string $key)
 * @method static \MkyCore\Cache store(string $key, mixed $data, int $expiration = 3600)
 * @method static array getAll(bool $meta = false)
 * @method static int removeExpired(string $key)
 * @method static \MkyCore\Cache removeAll(string $key)
 * @method static \MkyCore\Cache use(string $name, array $config = [])
 * @see \MkyCore\Cache
 */
class Cache extends Facade
{
    protected static string $accessor = \MkyCore\Cache::class;
}