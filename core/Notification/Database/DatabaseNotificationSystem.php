<?php


namespace MkyCore\Notification\Database;

use MkyCore\Abstracts\Entity;
use MkyCore\Database;
use MkyCore\Interfaces\NotificationSystemInterface;

class DatabaseNotificationSystem implements NotificationSystemInterface
{

    public function __construct(private readonly NotificationManager $manager)
    {

    }

    public function send(Entity $notifiable, array $message): bool
    {
        $notification = new Notification(array_merge([
            'entity' => Database::stringifyEntity($notifiable),
            'entity_id' => $notifiable->{$notifiable->getPrimaryKey()}()
        ], $message));
        $this->manager->save($notification);
        return true;
    }
}