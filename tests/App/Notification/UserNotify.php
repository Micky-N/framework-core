<?php


namespace MkyCore\Tests\App\Notification;


use MkyCore\Traits\Notify;

class UserNotify
{
    use Notify;

    public function __construct(private readonly int $id, private readonly string $name)
    {
    }
}