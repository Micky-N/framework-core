<?php

namespace MkyCore\Remember;

use MkyCore\Abstracts\Entity;
use ReflectionException;

/**
 * @Manager('MkyCore\Remember\RememberTokenManager')
 */
class RememberToken extends Entity
{
    private ?int $id = null;
    private string $entity;
    private string|int $entityId;
    private string $provider;
    private string $selector;
    private string $validator;
    private ?string $createdAt = null;

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
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
    public function selector(): string
    {
        return $this->selector;
    }

    /**
     * @param string $selector
     */
    public function setSelector(string $selector): void
    {
        $this->selector = $selector;
    }

    /**
     * @return string
     */
    public function validator(): string
    {
        return $this->validator;
    }

    /**
     * @param string $validator
     */
    public function setValidator(string $validator): void
    {
        $this->validator = $validator;
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
     * @throws ReflectionException
     */
    public function user(): Entity|false
    {
        $entity = str_replace('.', '\\', $this->entity);
        /** @var Entity $entity */
        $entity = new $entity();
        $manager = $entity->getManager();
        return $manager->find($this->entityId);
    }

    /**
     * @return string
     */
    public function provider(): string
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     */
    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }
}