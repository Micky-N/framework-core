<?php

namespace MkyCore\Tests;

use MkyCore\App;
use MkyCore\Exceptions\Notification\NotificationException;
use MkyCore\Exceptions\Notification\NotificationNotAliasException;
use MkyCore\Exceptions\Notification\NotificationNotMessageException;
use MkyCore\Exceptions\Notification\NotificationNotViaException;
use MkyCore\Exceptions\Notification\NotificationSystemException;
use PHPUnit\Framework\TestCase;
use MkyCore\Tests\App\Notification\NotificationNotAliasTest;
use MkyCore\Tests\App\Notification\NotificationNotInstantiableTest;
use MkyCore\Tests\App\Notification\NotificationNotMessageTest;
use MkyCore\Tests\App\Notification\NotificationNotSendTest;
use MkyCore\Tests\App\Notification\NotificationNotToMethodTest;
use MkyCore\Tests\App\Notification\NotificationNotViaTest;
use MkyCore\Tests\App\Notification\NotificationTest;
use MkyCore\Tests\App\Notification\ReturnNotificationClass;
use MkyCore\Tests\App\Notification\UserNotify;

class NotifyTest extends TestCase
{
    /**
     * @var UserNotify
     */
    private UserNotify $user;

    public function setUp(): void
    {
        $this->user = new UserNotify(1, 'micky');
        App::setAlias('test', \MkyCore\Tests\App\Notification\NotificationSystem::class);
        App::setAlias('notSend', \MkyCore\Tests\App\Notification\NotificationNotSendSystem::class);
    }

    public function testActionNotification()
    {
        $process = [
            'default' => ['test' => 'default'],
            'action' => ['test' => 'action'],
        ];
        // No Action
        $this->user->notify(new NotificationTest($process));
        $this->assertEquals($process['default'], ReturnNotificationClass::getReturn());

        // With Action
        $this->user->notify(new NotificationTest($process, 'action'));
        $this->assertEquals($process['action'], ReturnNotificationClass::getReturn());
    }

    public function testNotViaNotification()
    {
        try {
            $this->user->notify(new NotificationNotViaTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationNotViaException::class, $ex);
        }
    }

    public function testNotAliasNotification()
    {
        try {
            $this->user->notify(new NotificationNotAliasTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationNotAliasException::class, $ex);
        }
    }

    public function testNotToMethodNotification()
    {
        try {
            $this->user->notify(new NotificationNotToMethodTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationException::class, $ex);
        }
    }

    public function testNotMessageNotification()
    {
        try {
            $this->user->notify(new NotificationNotMessageTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationNotMessageException::class, $ex);
        }

    }

    public function testNotSendNotification()
    {
        try {
            $this->user->notify(new NotificationNotSendTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationSystemException::class, $ex);
        }

    }
}
