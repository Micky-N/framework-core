<?php


namespace MkyCore\Tests\App\Notification;


class NotificationNotToMethodTest implements \MkyCore\Interfaces\NotificationInterface
{
    /**
     * @var mixed
     */
    private mixed $process;
    private string $action;

    public function __construct($process, string $action = '')
    {
        $this->process = $process;
        $this->action = $action;
    }

    /**
     * @inheritDoc
     */
    public function via($notifiable): array
    {
        return ['test'];
    }
}