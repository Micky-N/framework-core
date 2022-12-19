<?php

namespace MkyCore\Traits;

use MkyCore\Api\JsonWebToken;
use MkyCore\Api\JWT;
use MkyCore\Api\NewJWT;
use MkyCore\QueryBuilderMysql;
use MkyCore\RelationEntity\HasMany;

/**
 * @method NewJWT createJwt(string $name)
 * @method bool verifyJwt(string $jwt)
 * @method JsonWebToken|false retrieveJwt(string $jwt)
 * @method HasMany tokens()
 * @see JWT
 */
trait ApiToken
{
    public function __call(string $method, array $arguments)
    {
        $accessTokenEntity = new JsonWebToken();
        $jwt = new JWT($this, $accessTokenEntity->getManager());
        if (method_exists($jwt, $method)) {
            return call_user_func_array([$jwt, $method], $arguments);
        }
        return null;
    }

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
}