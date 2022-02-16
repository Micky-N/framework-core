<?php


namespace MkyCore\Tests\App\Event;


use MkyCore\Interfaces\EventInterface;
use MkyCore\Interfaces\ListenerInterface;

class TestAliasListener implements ListenerInterface
{

    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(EventInterface $event)
    {
        $event->getTarget()->setCompleted(true);
    }
}