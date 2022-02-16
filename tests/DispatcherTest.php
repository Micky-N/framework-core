<?php

namespace MkyCore\Tests;

use MkyCore\App;
use MkyCore\Exceptions\Dispatcher\EventNotFoundException;
use MkyCore\Exceptions\Dispatcher\EventNotImplementException;
use MkyCore\Exceptions\Dispatcher\ListenerNotFoundException;
use MkyCore\Exceptions\Dispatcher\ListenerNotImplementException;
use PHPUnit\Framework\TestCase;
use MkyCore\Tests\App\Event\TestAliasListener;
use MkyCore\Tests\App\Event\TestEvent;
use MkyCore\Tests\App\Event\TestNotFoundEvent;
use MkyCore\Tests\App\Event\TestNotImplementEvent;
use MkyCore\Tests\App\Event\TestNoAliasListener;
use MkyCore\Tests\App\Event\TestNotImplementListener;
use MkyCore\Tests\App\Event\TestPropagationListener;
use MkyCore\Tests\App\Event\TodoTestClass;

class DispatcherTest extends TestCase
{
    /**
     * @var mixed
     */
    private $todoTest;

    public function setUp(): void
    {
        $this->todoTest = new TodoTestClass('eat burger', false);
        App::setEvents(\MkyCore\Tests\App\Event\TestEvent::class, 'test', TestAliasListener::class);
        App::setEvents(\MkyCore\Tests\App\Event\TestEvent::class, 'propagation', TestPropagationListener::class);
        App::setEvents(\MkyCore\Tests\App\Event\TestEvent::class, 'notImplement', TestNotImplementListener::class);
    }

    public function testConstructor()
    {
        $event = TestEvent::dispatch($this->todoTest);
        $this->assertInstanceOf(TestEvent::class, $event);
        $this->assertInstanceOf(TodoTestClass::class, $event->getTarget());
    }

    public function testEventWithAlias()
    {
        TestEvent::dispatch($this->todoTest, 'test');
        $this->assertTrue($this->todoTest->getCompleted());
    }

    public function testEventWithNoAlias()
    {
        try {
            TestEvent::dispatch($this->todoTest, TestNoAliasListener::class);
        }catch(\Exception $ex){
            $this->assertInstanceOf(ListenerNotFoundException::class, $ex);
        }
    }

    public function testEventStopPropagation()
    {
        $event = TestEvent::dispatch($this->todoTest, ['propagation', 'test', TestNoAliasListener::class]);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertFalse($this->todoTest->getCompleted());
    }

    public function testEventNotImplementException()
    {
        try {
            TestNotImplementEvent::dispatch($this->todoTest);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(EventNotImplementException::class, $ex);
        }
    }

    public function testEventNotFoundException()
    {
        try {
            TestNotFoundEvent::dispatch($this->todoTest, ['test']);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(EventNotFoundException::class, $ex);
        }
    }

    public function testListenerNotFoundException()
    {
        try {
            TestEvent::dispatch($this->todoTest, 'notFound');
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ListenerNotFoundException::class, $ex);
        }
    }

    public function testListenerNotImplementException()
    {
        try {
            TestEvent::dispatch($this->todoTest, 'notImplement');
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ListenerNotImplementException::class, $ex);
        }
    }
}
