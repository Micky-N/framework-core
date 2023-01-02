<?php

namespace MkyCore\Facades;

use MkyCore\AuthManager;
use MkyCore\Interfaces\AuthSystemInterface;

/**
 * @method static bool attempt(array $credentials)
 * @method static bool isLogin()
 * @method static void logout()
 * @method static void setBaseProvider(string $BaseProvider)
 * @method static AuthManager use(string $provider, bool $replace = false)
 * @method static array|mixed getProviderConfig(?string $key = null)
 * @method static string getProviderName()
 * @method static \MkyCore\Abstracts\Entity|bool|null user()
 * @method static AuthSystemInterface getManager()
 * @see \MkyCore\AuthManager
 */
class Auth extends \MkyCore\Abstracts\Facade
{
    protected static string $accessor = \MkyCore\AuthManager::class;
}