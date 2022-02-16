<?php


namespace MkyCore\Tests\App\Event;



use MkyCore\Interfaces\EventInterface;
use MkyCore\Interfaces\ListenerInterface;

class TestNoAliasListener implements ListenerInterface
{

    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(EventInterface $event)
    {
        $event->getTarget()->setName('burger eaten');
    }
}