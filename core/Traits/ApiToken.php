<?php

namespace MkyCore\Traits;

use MkyCore\Api\JsonWebToken;
use MkyCore\Api\JWT;
use MkyCore\QueryBuilderMysql;
use MkyCore\RelationEntity\HasMany;

/**
 * @see JWT
 */
trait ApiToken
{

    public function createJwt(string $name)
    {
        return JWT::createJwt($this, $name);
    }

    /**
     * @return HasMany
     * @throws \ReflectionException
     */
    public function tokens(string $name = ''): HasMany
    {
        return $this->hasMany(JsonWebToken::class, 'entity_id')->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) use ($name) {
            $queryBuilderMysql->where('entity', JWT::stringifyEntity($this));
            if ($name) {
                $queryBuilderMysql = $queryBuilderMysql->where('json_web_tokens.name', $name);
            }
            return $queryBuilderMysql;
        });
    }

    public function verifyJwt(string $jwt): bool
    {
        return JWT::verifyJwt($jwt);
    }

    public function retrieveJwt(string $jwt): JsonWebToken|false
    {
        return JWT::retrieveJwt($jwt);
    }

    public function revokeJwt(string $jwt): JsonWebToken|false
    {
        return JWT::revokeJwt($jwt);
    }
}