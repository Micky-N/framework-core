<?php


namespace MkyCore\Traits;


use MkyCore\App;
use MkyCore\Exceptions\Notification\NotificationException;
use MkyCore\Exceptions\Notification\NotificationNotAliasException;
use MkyCore\Exceptions\Notification\NotificationNotMessageException;
use MkyCore\Exceptions\Notification\NotificationNotViaException;
use MkyCore\Exceptions\Notification\NotificationSystemException;
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
     * @throws NotificationException
     * @throws NotificationNotAliasException
     * @throws NotificationNotViaException
     * @throws NotificationSystemException
     * @throws ReflectionException
     * @throws NotificationNotMessageException
     */
    public function notify(NotificationInterface $notification)
    {
        if(!is_array($notification->via($this)) || count($notification->via($this)) < 1){
            throw new NotificationNotViaException(sprintf('Notification system is required in the method %s::via()', get_class($notification)));
        }
        foreach ($notification->via($this) as $via){
            $alias = App::getAlias($via);
            if(is_null($alias)){
                throw new NotificationNotAliasException("$via alias is not defined in the Provider");
            }
            $class = new ReflectionClass($alias);
            if(!method_exists($notification, 'to'.ucfirst($via))){
                throw new NotificationException(sprintf("%s must implement the '%s' method", get_class($notification), 'to'.ucfirst($via)));
            }
            $message = $notification->{'to'.ucfirst($via)}($this);
            if(empty($message)){
                throw new NotificationNotMessageException(sprintf('%s::%s() message is required', get_class($notification), 'to'.ucfirst($via)));
            }
            if(!($class->newInstance() instanceof NotificationSystemInterface)){
                throw new NotificationSystemException(sprintf("%s must implement %s interface", $class->getName(), NotificationSystemInterface::class));
            }
            call_user_func_array([$class->newInstance(), 'send'], [$this, $message]);
        }
        return true;
    }
}