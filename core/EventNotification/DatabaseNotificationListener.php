<?php

namespace MkyCore\EventNotification;

use MkyCore\Database;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\EventInterface;
use MkyCore\Interfaces\ListenerInterface;
use ReflectionException;

class DatabaseNotificationListener implements ListenerInterface
{

    const NEW_EVENT = 'new event';

    /**
     * @inheritDoc
     * @param EventInterface $event
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function handle(EventInterface $event)
    {
        $notifiable = $event->getTarget();
        $notification = new Notification(array_merge([
            'entity' => Database::stringifyEntity($notifiable),
            'entity_id' => $notifiable->{$notifiable->getPrimaryKey()}(),
            'type' => self::NEW_EVENT,
            'data' => ['message' => 'New event', 'url' => route('home.index')]
        ], $event->getParams()));
        $manager = app()->get(NotificationManager::class);
        $manager->save($notification);
    }
}