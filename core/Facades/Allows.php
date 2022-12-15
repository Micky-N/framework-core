<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;

/**
 * @method static \MkyCore\Permission define(string $name, \Closure $callback)
 * @method static bool authorize(string $name, \MkyCore\Abstracts\Entity $user, \MkyCore\Abstracts\Entity|string $entity, array $options = [])
 * @method static \MkyCore\RedirectResponse|bool routeAuthorize(string $name, \MkyCore\Abstracts\Entity|string $entity, array $options = [])
 * @method static \Closure[] getCallbacks()
 * @method static \Closure|null getCallback(string $name)
 * @see \MkyCore\Permission
 */
class Allows extends Facade
{
    protected static string $accessor = \MkyCore\Permission::class;
}