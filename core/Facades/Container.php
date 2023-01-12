<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;

/**
 * @method static mixed get(string $alias, ...$options)
 * @method static mixed getInstance(string $alias)
 * @method static bool hasInstance(string $alias)
 * @method static bool has(string $alias)
 * @method static \MkyCore\Container setInstance(string $alias, mixed $instance)
 * @method static \MkyCore\Container bind(string $alias, mixed $concrete)
 * @method static \MkyCore\Container singleton(string $alias, mixed $concrete)
 * @method static \MkyCore\Container forceSingleton(string $alias, mixed $concrete)
 * @method static \MkyCore\Container removeInstance(string $alias)
 * @method static array getEntries()
 * @method static array getSharedInstances()
 * @see \MkyCore\Container
 */
class Container extends Facade
{
    protected static string $accessor = \MkyCore\Container::class;
}