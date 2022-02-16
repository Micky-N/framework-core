<?php


namespace MkyCore\Tests\App\Notification;


use MkyCore\Interfaces\NotificationSystemInterface;

class NotificationSystem implements NotificationSystemInterface
{

    private array $messageTest;

    public function __construct()
    {
        $this->messageTest = [];
    }

    public function send($notifiable, array $message): void
    {
        ReturnNotificationClass::setReturn($message);
    }
}