<?php

namespace MkyCore\Notification\Database;

use Carbon\Carbon;
use DateTime;
use MkyCore\Abstracts\Entity;

/**
 * @Manager('MkyCore\Notification\Database\NotificationManager')
 */
class Notification extends Entity
{

    /**
    * @PrimaryKey
    */
    private ?int $id = null;
	private string $entity;
	private int|string $entityId;
	private ?string $type = null;
	private string $data;
	private ?string $readAt = null;
	private ?string $createdAt = null;
	private ?string $updatedAt = null;

    public function id(): ?int
    {
        return $this->id;
    }
    
    public function setId(int $id)
    {
        $this->id = $id;
    }

	public function entity(): string
    {
        return $this->entity;
    }
    
    public function setEntity(string $entity)
    {
        $this->entity = $entity;
    }

	public function entityId(): int|string
    {
        return $this->entityId;
    }
    
    public function setEntityId(int|string $entityId)
    {
        $this->entityId = $entityId;
    }

	public function type(): ?string
    {
        return $this->type;
    }
    
    public function setType(?string $type)
    {
        $this->type = $type;
    }

	public function data(): string
    {
        return $this->data;
    }
    
    public function setData(string|array $data)
    {
        if(is_array($data)){
            $data = json_encode($data);
        }
        $this->data = $data;
    }

	public function readAt()
    {
        return $this->readAt;
    }
    
    public function setReadAt(string|DateTime|null $readAt)
    {
        if($readAt instanceof DateTime){
            $readAt = $readAt->format('Y-m-d H:i:s');
        }
        $this->readAt = $readAt;
    }

	public function createdAt()
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

	public function updatedAt()
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function markAsRead(): Entity|bool
    {
        $this->setReadAt(now());
        return $this->getManager()->update($this);
    }
}