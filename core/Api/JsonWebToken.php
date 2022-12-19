<?php

namespace MkyCore\Api;


/**
 * @Manager('MkyCore\Api\JsonWebTokenManager')
 */
class JsonWebToken extends \MkyCore\Abstracts\Entity
{

    private $id;
    private $entity;
    private $entityId;
    private $token;
    private $name;
    private $expireAt;
    private $createdAt;

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function entity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return mixed
     */
    public function entityId()
    {
        return $this->entityId;
    }

    /**
     * @param mixed $entityId
     */
    public function setEntityId($entityId): void
    {
        $this->entityId = $entityId;
    }

    public function name()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function token()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function expireAt()
    {
        return $this->expireAt;
    }

    /**
     * @param mixed $expireAt
     */
    public function setExpireAt($expireAt): void
    {
        $this->expireAt = $expireAt;
    }

    /**
     * @return mixed
     */
    public function createdAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}