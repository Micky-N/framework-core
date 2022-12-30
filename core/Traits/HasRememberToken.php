<?php

namespace MkyCore\Traits;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Database;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\QueryBuilderMysql;
use MkyCore\RelationEntity\HasMany;
use MkyCore\Remember\RememberMe;
use MkyCore\Remember\RememberToken;
use ReflectionException;

trait HasRememberToken
{
    /**
     * Create a remember token in set in database
     *
     * @param string $provider
     * @return Entity|bool
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function rememberMe(string $provider = ''): Entity|bool
    {
        $provider = $provider ?: config('auth.default.provider');

        $this->revokeRememberToken();
        if ($newToken = RememberMe::create($provider)) {
            $newToken->setEntity(Database::stringifyEntity($this));
            $newToken->setProvider($provider);
            return $this->rememberToken()->add($newToken);
        }
        return false;
    }

    /**
     * Delete remember token from database
     * and cookie
     *
     * @return bool
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws Exception
     */
    public function revokeRememberToken(): bool
    {
        /**
         * @var Entity $this
         * @var RememberToken $rememberToken
         */
        if ($this->rememberToken()->clear()) {
            return RememberMe::revokeToken();
        }
        return false;
    }

    /**
     * Get remember token link to user
     *
     * @return HasMany|bool
     */
    public function rememberToken(): HasMany|bool
    {
        return $this->hasMany(RememberToken::class, 'entity_id')
            ->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) {
                $queryBuilderMysql->where('entity', Database::stringifyEntity($this));
                return $queryBuilderMysql;
            });
    }

    /**
     * Get remember token from cookie
     *
     * @return string|false
     */
    public function rememberCookie(): string|false
    {
        if ($token = $this->rememberToken()->get()) {
            $selector = $token[0]->selector();
            return RememberMe::retrieveToken($selector);
        }
        return false;
    }

    /**
     * Check if remember token in user is valid
     *
     * @return bool
     */
    public function checkRememberToken(): bool
    {
        $rememberToken = $this->rememberToken()->get();
        if (!$rememberToken) {
            return false;
        }
        $rememberToken = reset($rememberToken);
        return RememberMe::check($rememberToken);
    }
}