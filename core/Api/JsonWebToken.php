<?php

namespace MkyCore\Api;

use MkyCore\Abstracts\Entity;

/**
 * @Manager('MkyCore\Api\JsonWebTokenManager')
 */
class JsonWebToken extends Entity
{

    private ?int $id = null;
    private string $entity;
    private int|string $entityId;
    private string $token;
    private string $name;
    private int $expireAt;
    private string $createdAt;

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function entity(): string
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     */
    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return string|int
     */
    public function entityId(): string|int
    {
        return $this->entityId;
    }

    /**
     * @param int|string $entityId
     */
    public function setEntityId(int|string $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function expireAt(): int
    {
        return $this->expireAt;
    }

    /**
     * @param int $expireAt
     */
    public function setExpireAt(int $expireAt): void
    {
        $this->expireAt = $expireAt;
    }

    /**
     * @return string
     */
    public function createdAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}