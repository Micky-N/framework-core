<?php


namespace MkyCore\Tests\App\Event;


use MkyCore\Abstracts\Event;
use MkyCore\Traits\Dispatcher;

class TestNotImplementEvent
{

    use Dispatcher;
    public function __construct()
    {

    }
}