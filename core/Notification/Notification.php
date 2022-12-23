<?php


namespace MkyCore\Notification;


use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Exceptions\Notification\NotificationException;
use MkyCore\Interfaces\NotificationInterface;
use MkyCore\Traits\Notify;
use ReflectionClass;
use ReflectionException;

class Notification
{
    /**
     * Send notification with notification application
     *
     * @param Entity[] $notifiables
     * @param NotificationInterface $notification
     * @throws NotificationException
     */
    public static function send(array $notifiables, NotificationInterface $notification): void
    {
        foreach ($notifiables as $notifiable) {
            $usingTrait = in_array(
                Notify::class,
                array_keys((new ReflectionClass(get_class($notifiable)))->getTraits())
            );
            if ($usingTrait) {
                $notifiable->notify($notification);
            } else {
                throw new NotificationException(sprintf('Model %s must use %s trait', get_class($notifiable), Notify::class));
            }
        }
    }
}