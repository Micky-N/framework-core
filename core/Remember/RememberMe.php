<?php

namespace MkyCore\Remember;

use Exception;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Facades\Cookie;
use MkyCore\Str;
use ReflectionException;

class RememberMe
{
    const PREFIX_ID = 'remember_me';
    const SEPARATOR = ':';

    /**
     * Check if remember token is valid
     *
     * @param RememberToken $rememberToken
     * @return bool
     */
    public static function check(RememberToken $rememberToken): bool
    {
        $retrievedToken = self::retrieveToken($rememberToken->selector());
        if (!$retrievedToken) {
            return false;
        }
        list($selector, $validator) = self::parseTokenFromCookie($retrievedToken);
        if (!$selector || $selector !== $rememberToken->selector() || !$validator) {
            return false;
        }
        return password_verify($validator, $rememberToken->validator());
    }

    /**
     * Get remember token from cookie
     *
     * @param string $selector
     * @return string|false
     */
    public static function retrieveToken(string $selector): string|false
    {
        $token = Cookie::get(self::PREFIX_ID);
        if (!$token) {
            return false;
        }
        return str_starts_with($token, $selector . self::SEPARATOR) ? $token : false;
    }

    /**
     * Separate selector:validator
     *
     * @param string $token
     * @return array|null
     */
    private static function parseTokenFromCookie(string $token): ?array
    {
        $parts = explode(':', $token);
        if ($parts && count($parts) == 2) {
            return [$parts[0], $parts[1]];
        }
        return null;
    }

    /**
     * Create a remember token and set in cookie
     *
     * @param string $authProvider
     * @return RememberToken|false
     * @throws ReflectionException
     */
    public static function create(string $authProvider = ''): RememberToken|false
    {
        $expireTime = self::getExpireTime($authProvider);
        list($selector, $validator, $token) = self::generateTokens();
        $rememberToken = new RememberToken([
            'selector' => $selector,
            'validator' => password_hash($validator, PASSWORD_BCRYPT)
        ]);
        if (!Cookie::set(self::PREFIX_ID, $token, $expireTime)) {
            return false;
        }
        return $rememberToken;
    }

    private static function getExpireTime(string $authProvider = '')
    {
        if(!$authProvider){
            $authProvider = config('auth.default.provider');
        }
        return config('auth.remember.' . $authProvider . '.lifetime', '+1 month');
    }

    /**
     * Generate selector:validator token
     *
     * @throws Exception
     */
    private static function generateTokens(): array
    {
        $selector = Str::random();
        $validator = Str::random(32);
        $token = $selector . self::SEPARATOR . $validator;
        return [$selector, $validator, $token];
    }

    /**
     * Remove remember token from cookie
     *
     * @return bool
     */
    public static function revokeToken(): bool
    {
        return Cookie::remove(self::PREFIX_ID);
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public static function getRememberTokenEntity(string $token): RememberToken|false
    {
        list($selector, $validator) = self::parseTokenFromCookie($token);
        $rememberTokenEntity = self::rememberTokenManager()->where('selector', $selector)->first();
        if (!$rememberTokenEntity) {
            return false;
        }
        /** @var RememberToken $rememberTokenEntity */
        if (!password_verify($validator, $rememberTokenEntity->validator())) {
            return false;
        }
        return $rememberTokenEntity;
    }

    /**
     * Get remember token manager
     *
     * @return RememberTokenManager
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    private static function rememberTokenManager(): RememberTokenManager
    {
        return app()->get(RememberTokenManager::class);
    }
}