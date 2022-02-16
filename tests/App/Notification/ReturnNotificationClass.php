<?php


namespace MkyCore\Tests\App\Notification;


class ReturnNotificationClass
{
    private static $return = null;

    public static function setReturn($value)
    {
        self::$return = $value;
    }

    /**
     * @return mixed
     */
    public static function getReturn()
    {
        return self::$return;
    }


}