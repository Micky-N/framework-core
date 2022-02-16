<?php


namespace MkyCore\Tests\App\Event;


use MkyCore\Interfaces\EventInterface;
use MkyCore\Interfaces\ListenerInterface;

class TestPropagationListener implements ListenerInterface
{

    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(EventInterface $event)
    {
        $event->stopPropagation(true);
    }
}