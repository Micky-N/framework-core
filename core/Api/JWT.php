<?php

namespace MkyCore\Api;

use MkyCore\Abstracts\Entity;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class JWT
{

    private const HEADER = ['typ' => 'JWT', 'alg' => 'HS256'];

    public function __construct(private readonly Entity $entity)
    {
    }

    /**
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function create(): string
    {
        $expire = time() + 10;
        $payload = $this->makePayload($expire);

        $base64UrlHeader = $this->toBase64Url(json_encode(self::HEADER));
        $base64UrlPayload = $this->toBase64Url(json_encode($payload));

        $signature = $this->makeSignature($base64UrlHeader, $base64UrlPayload, $expire);

        $base64UrlSecurity = $this->toBase64Url($signature);

        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = app()->get(ApiTokenManager::class);

        $apiTokenManager->create([
            'entity' => get_class($this->entity) . '::' . $this->entity->{$this->entity->getPrimaryKey()}(),
            'token' => $base64UrlSecurity,
            'expireAt' => $expire,
            'createdAt' => now()->format('Y-m-d H:i:s')
        ]);

        return $base64UrlPayload;
    }

    /**
     * @throws ReflectionException
     */
    private function makePayload(string $expire): array
    {
        $primaryKey = $this->entity->getPrimaryKey();
        return [
            'entity' => get_class($this),
            $primaryKey => $this->entity->{$primaryKey}(),
            'expireAt' => $expire,
        ];
    }

    private function toBase64Url(string $hash): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($hash));
    }

    private function makeSignature(string $base64UrlHeader, string $base64UrlPayload, int $expire): string
    {
        return hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $expire, true);
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function verify(string $jwt): bool
    {
        // split the jwt
        $payload = json_decode(base64_decode($jwt));
        $expireAt = $payload->expireAt;

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        if (($expireAt - time()) < 0) {
            return false;
        }
        
        $base64UrlHeader = $this->toBase64Url(json_encode(self::HEADER));
        $signature = $this->makeSignature($base64UrlHeader, $jwt, $expireAt);

        $base64UrlSecurity = $this->toBase64Url($signature);
        $manager = app()->get(ApiTokenManager::class);

        $isValid = $manager->where('token', $base64UrlSecurity)->first();

        // verify if the signature created exists
        return !!$isValid;
    }
}