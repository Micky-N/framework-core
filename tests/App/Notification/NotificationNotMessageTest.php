<?php


namespace MkyCore\Tests\App\Notification;


class NotificationNotMessageTest implements \MkyCore\Interfaces\NotificationInterface
{

    /**
     * @var mixed
     */
    private $process;
    private string $action;

    public function __construct($process, string $action = '')
    {
        $this->process = $process;
        $this->action = $action;
    }

    /**
     * @inheritDoc
     */
    public function via($notifiable)
    {
        return ['test'];
    }

    public function toTest()
    {
        return;
    }
}