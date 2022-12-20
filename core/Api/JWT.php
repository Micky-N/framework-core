<?php

namespace MkyCore\Api;

use Exception;
use MkyCore\Abstracts\Entity;

class Jwt
{

    private const HEADER = ['typ' => 'Jwt', 'alg' => 'HS256'];

    public static function createJwt(Entity $entity, string $name)
    {
        $expireTime = time() + (60 * (float)config('jwt.lifetime', 1));
        $payload = self::makePayload($entity, $expireTime);

        $base64UrlHeader = self::toBase64Url(json_encode(self::HEADER));
        $base64UrlPayload = self::toBase64Url(json_encode($payload));

        $signature = self::makeSignature($base64UrlHeader, $base64UrlPayload, env('API_SECRET', $expireTime));

        $base64UrlSecurity = self::toBase64Url($signature);

        $jsonWebToken = $entity->tokens()->add(new JsonWebToken([
            'entity' => self::stringifyEntity($entity),
            'name' => $name,
            'token' => $base64UrlSecurity,
            'expireAt' => $expireTime,
            'createdAt' => now()->format('Y-m-d H:i:s')
        ]));

        return new NewJWT($jsonWebToken, $base64UrlPayload);
    }

    private static function makePayload(Entity $entity, int $expireTime): array
    {
        $primaryKey = $entity->getPrimaryKey();
        $defaultPayload = [
            'iat' => time(),
            'entity' => self::stringifyEntity($entity),
            'id' => $entity->{$primaryKey}(),
            'expireAt' => $expireTime,
        ];
        $customPayload = [];

        if (method_exists($entity, 'payload')) {
            $customPayload = $entity->payload();
        }

        return array_replace_recursive($defaultPayload, $customPayload);
    }

    public static function stringifyEntity(Entity $entity): string
    {
        return str_replace('\\', '.', get_class($entity));
    }

    private static function toBase64Url(string $hash): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($hash));
    }

    private static function makeSignature(string $base64UrlHeader, string $base64UrlPayload, string $secret): string
    {
        return hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, base64_encode($secret), true);
    }

    public static function verifyJwt(string $jwt): bool
    {
        if (!($jwtEntity = $jwtEntity = self::retrieveJwt($jwt))) {
            return false;
        }
        return ($jwtEntity->expireAt() - time()) > 0;
    }

    public static function retrieveJwt(string $jwt): JsonWebToken|false
    {
        // split the jwt
        $payload = json_decode(base64_decode($jwt));
        $secret = env('API_SECRET', $payload->expireAt);

        $base64UrlHeader = self::toBase64Url(json_encode(self::HEADER));
        $signature = self::makeSignature($base64UrlHeader, $jwt, $secret);

        $base64UrlSecurity = self::toBase64Url($signature);

        return self::jsonWebTokenManager()->where('token', $base64UrlSecurity)->first();
    }

    private static function jsonWebTokenManager(): JsonWebTokenManager
    {
        return app()->get(JsonWebTokenManager::class);
    }

    public static function revokeJwt(string $jwt): JsonWebToken|false
    {
        $jwtEntity = self::retrieveJwt($jwt);
        if (!$jwtEntity) {
            return false;
        }
        try {
            /** @var JsonWebToken $res */
            $res = self::jsonWebTokenManager()->delete($jwtEntity);
            return $res;
        } catch (Exception $exception) {
            return false;
        }
    }
}