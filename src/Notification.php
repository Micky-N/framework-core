<?php


namespace MkyCore;


use Exception;
use MkyCore\Exceptions\Notification\NotificationException;
use MkyCore\Traits\Notify;
use ReflectionClass;
use ReflectionException;

class Notification
{
    /**
     * Send notification with notification application
     *
     * @param array $notifiables
     * @param Exception $notification
     * @throws NotificationException
     * @throws ReflectionException
     */
    public static function send(array $notifiables, Exception $notification): void
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