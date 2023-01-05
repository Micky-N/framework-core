<?php

namespace MkyCore\Facades;


use Closure;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Facade;
use MkyCore\Permission;
use MkyCore\RedirectResponse;

/**
 * @method static Permission define(string $name, Closure $callback)
 * @method static bool authorize(string $name, Entity $user, Entity|string $entity, array $options = [])
 * @method static RedirectResponse|bool routeAuthorize(string $name, Entity|string $entity, array $options = [])
 * @method static Closure[] getCallbacks()
 * @method static Closure|null getCallback(string $name)
 * @see \MkyCore\Permission
 */
class Allows extends Facade
{
    protected static string $accessor = Permission::class;
}