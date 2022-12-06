<?php


namespace MkyCore\Tests\App\Event;


use MkyCore\Abstracts\Event;

class TestNotFoundEvent extends Event
{
    /**
     * Event constructor.
     * @param mixed $target
     * @param array $actions
     * @param array $params
     */
    public function __construct(mixed $target, array $actions, array $params = [])
    {

    }
}