<?php


namespace MkyCore\Tests\App\Event;


use MkyCore\Event;

class TestNotFoundEvent extends Event
{
    /**
     * Event constructor.
     * @param mixed $target
     * @param array $actions
     * @param array $params
     */
    public function __construct($target, array $actions, array $params = [])
    {
        parent::__construct($target, $actions, $params);
    }
}