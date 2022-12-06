<?php


namespace MkyCore\Tests\App\Event;



use MkyCore\Abstracts\Event;

class TestEvent extends Event
{
    /**
     * Event constructor.
     * @param mixed $target
     * @param array $actions
     * @param array $params
     */
    public function __construct(protected mixed $target, protected array $actions, protected array $params = [])
    {

    }
}