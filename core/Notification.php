<?php


namespace MkyCore;


use Exception;
use ReflectionClass;
use MkyCore\Traits\Notify;
use ReflectionException;
use MkyCore\Interfaces\NotificationInterface;
use MkyCore\Exceptions\Notification\NotificationException;

class Notification
{
    /**
     * Send notification with notification application
     *
     * @param array $notifiables
     * @param NotificationInterface $notification
     * @throws NotificationException
     * @throws ReflectionException
     */
    public static function send(array $notifiables, NotificationInterface $notification)
    {
        foreach ($notifiables as $notifiable){
            $usingTrait = in_array(
                Notify::class,
                array_keys((new ReflectionClass(get_class($notifiable)))->getTraits())
            );
            if($usingTrait == true){
                $notifiable->notify($notification);
            }else{
                throw new NotificationException(sprintf('Model %s must use %s trait', get_class($notifiable), Notify::class));
            }
        }
    }
}