<?php

namespace MkyCore\Facades;

/**
 * @method static bool attempt(array $credentials)
 * @method static bool isLogin()
 * @method static void logout()
 * @method static \MkyCore\Abstracts\Entity|bool|null user()
 * @see \MkyCore\AuthManager
 */
class Auth extends \MkyCore\Abstracts\Facade
{
    protected static string $accessor = \MkyCore\AuthManager::class;
}