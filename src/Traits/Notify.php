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
        if (!is_array($notification->via($this)) || count($notification->via($this)) < 1) {
            throw new NotificationSystemNotFoundAliasException(sprintf('Notification system is required in the method %s::via()', get_class($notification)));
        }
        foreach ($notification->via($this) as $via) {
            $alias = app()->getNotificationSystem($via);
            if (is_null($alias)) {
                throw new NotificationSystemNotFoundAliasException("$via alias is not defined in the EventServiceProvider");
            }

            $class = new ReflectionClass($alias);
            if (!method_exists($notification, 'to' . ucfirst($via))) {
                throw new NotificationSystemNotFoundAliasException(sprintf("%s must implement the '%s' method", get_class($notification), 'to' . ucfirst($via)));
            }

            $message = $notification->{'to' . ucfirst($via)}($this);
            if (empty($message)) {
                throw new NotificationNotMessageException(sprintf('%s::%s() message is required', get_class($notification), 'to' . ucfirst($via)));
            }

            if (!($class->newInstance() instanceof NotificationSystemInterface)) {
                throw new NotificationSystemException(sprintf("%s must implement %s interface", $class->getName(), NotificationSystemInterface::class));
            }

            call_user_func_array([$class->newInstance(), 'send'], [$this, $message]);
        }
        return true;
    }
}