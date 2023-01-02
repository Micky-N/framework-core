<?php

namespace MkyCore\PasswordReset;

use MkyCore\Abstracts\Entity;
use ReflectionException;

/**
 * @Manager('MkyCore\PasswordReset\PasswordResetManager')
 */
class PasswordReset extends Entity
{
    private ?int $id = null;
    private string $entity;
    private string|int $entityId;
    private string $token;
    private string $expiresAt;
    private ?string $createdAt = null;

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
     * @return int|string
     */
    public function entityId(): int|string
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
     * @return string
     */
    public function expiresAt(): string
    {
        return $this->expiresAt;
    }

    /**
     * @param string $expiresAt
     */
    public function setExpiresAt(string $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return string|null
     */
    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @param string|null $createdAt
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Entity|bool
     * @throws ReflectionException
     */
    public function user(): Entity|bool
    {
        $entity = str_replace('.', '\\', $this->entity);
        /** @var Entity $entityClass */
        $entityClass = new $entity();
        $manager = $entityClass->getManager();
        return $manager->find($this->entityId);
    }

    public function isValid(): bool
    {
        return now()->isBefore($this->expiresAt);
    }
}