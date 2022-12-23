<?php

namespace MkyCore\Traits;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Api\JsonWebToken;
use MkyCore\Api\JWT;
use MkyCore\Api\NewJsonWebToken;
use MkyCore\Database;
use MkyCore\QueryBuilderMysql;
use MkyCore\RelationEntity\HasMany;
use ReflectionException;

/**
 * @see JWT
 */
trait HasJwToken
{
    /**
     * Create token and save to database
     *
     * @throws ReflectionException
     */
    public function createJwt(string $name): NewJsonWebToken
    {
        /** @var Entity $this */
        return JWT::createJwt($this, $name);
    }

    /**
     * Get all tokens, can be filtered by name
     *
     * @param string $name
     * @return HasMany
     */
    public function tokens(string $name = ''): HasMany
    {
        /** @var Entity $this */
        return $this->hasMany(JsonWebToken::class, 'entity_id')->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) use ($name) {
            /** @var Entity $this */
            $queryBuilderMysql->where('entity', Database::stringifyEntity($this));
            if ($name) {
                $queryBuilderMysql = $queryBuilderMysql->where('json_web_tokens.name', $name);
            }
            return $queryBuilderMysql;
        });
    }

    /**
     * Check if token is valid
     *
     * @throws Exception
     */
    public function verifyJwt(string $jwt): bool
    {
        return JWT::verifyJwt($jwt);
    }

    /**
     * Retrieve JWT entity from token
     *
     * @throws Exception
     */
    public function retrieveJwt(string $jwt): JsonWebToken|false
    {
        return JWT::retrieveJwt($jwt);
    }

    /**
     * Delete JWT in database from token
     *
     * @throws Exception
     */
    public function revokeJwt(string $jwt): JsonWebToken|false
    {
        return JWT::revokeJwt($jwt);
    }
}