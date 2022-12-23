<?php


namespace MkyCore\Traits;


use MkyCore\Exceptions\Notification\NotificationNotAliasException;
use MkyCore\Exceptions\Notification\NotificationNotMessageException;
use MkyCore\Exceptions\Notification\NotificationSystemException;
use MkyCore\Exceptions\Notification\NotificationSystemNotFoundAliasException;
use MkyCore\Interfaces\NotificationInterface;
use MkyCore\Interfaces\NotificationSystemInterface;
use ReflectionClass;
use ReflectionException;

trait Notify
{

    /**
     * Run notification application
     *
     * @param NotificationInterface $notification
     * @return bool
     * @throws NotificationNotAliasException
     * @throws NotificationNotMessageException
     * @throws NotificationSystemNotFoundAliasException
     * @throws NotificationSystemException
     * @throws ReflectionException
     */
    public function notify(NotificationInterface $notification): bool
    {
        $systems = $notification->via($this);
        if (!is_array($systems) || count($systems) < 1) {
            throw new NotificationSystemNotFoundAliasException(sprintf('Notification system is required in the method %s::via()', get_class($notification)));
        }
        foreach ($systems as $system) {
            $alias = app()->getNotificationSystem($system);
            if (is_null($alias)) {
                throw new NotificationSystemNotFoundAliasException("$system alias is not defined in the EventServiceProvider");
            }

            $class = app()->get($alias);
            $notificationMethod = 'to' . ucfirst($system);
            if (!method_exists($notification, $notificationMethod)) {
                $notificationMethod = 'toArray';
                if(!method_exists($notification, $notificationMethod)){
                    throw new NotificationSystemNotFoundAliasException(sprintf("%s must implement the '%s' method", get_class($notification), 'to' . ucfirst($system)));
                }
            }

            $message = $notification->$notificationMethod($this);
            if (empty($message)) {
                throw new NotificationNotMessageException(sprintf('%s::%s() message is required', get_class($notification), 'to' . ucfirst($system)));
            }

            if (!($class instanceof NotificationSystemInterface)) {
                throw new NotificationSystemException(sprintf("%s must implement %s interface", get_class($class), NotificationSystemInterface::class));
            }

            call_user_func_array([$class, 'send'], [$this, $message]);
        }
        return true;
    }
}