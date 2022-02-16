<?php


namespace MkyCore\Tests\App\Notification;


use MkyCore\Traits\Notify;

class UserNotify
{
    use Notify;

    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}