<?php


namespace MkyCore\Tests\App\Notification;


class ReturnNotificationClass
{
    private static mixed $return = null;

    public static function setReturn($value): void
    {
        self::$return = $value;
    }

    /**
     * @return mixed
     */
    public static function getReturn(): mixed
    {
        return self::$return;
    }


}