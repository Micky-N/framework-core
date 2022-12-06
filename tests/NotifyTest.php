<?php

namespace MkyCore\Tests;

use MkyCore\Application;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Notification\NotificationException;
use MkyCore\Exceptions\Notification\NotificationNotAliasException;
use MkyCore\Exceptions\Notification\NotificationNotMessageException;
use MkyCore\Exceptions\Notification\NotificationSystemNotFoundAliasException;
use MkyCore\Exceptions\Notification\NotificationSystemException;
use PHPUnit\Framework\TestCase;
use MkyCore\Tests\App\Notification\NotificationNotAliasTest;
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

    /**
     * @return void
     * @throws \ReflectionException
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function setUp(): void
    {
        $this->app = new Application(__DIR__.DIRECTORY_SEPARATOR.'App');
        $this->user = new UserNotify(1, 'micky');
        $this->app->addNotificationSystem('test', \MkyCore\Tests\App\Notification\NotificationSystem::class);
        $this->app->addNotificationSystem('notSend', \MkyCore\Tests\App\Notification\NotificationNotSendSystem::class);
    }

    /**
     * @return void
     * @throws NotificationNotAliasException
     * @throws NotificationNotMessageException
     * @throws NotificationSystemException
     * @throws NotificationSystemNotFoundAliasException
     * @throws \ReflectionException
     */
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
            $this->assertInstanceOf(NotificationSystemNotFoundAliasException::class, $ex);
        }
    }

    public function testNotAliasNotification()
    {
        try {
            $this->user->notify(new NotificationNotAliasTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationSystemNotFoundAliasException::class, $ex);
        }
    }

    public function testNotToMethodNotification()
    {
        try {
            $this->user->notify(new NotificationNotToMethodTest(['test' => true]));
        } catch (\Exception $ex) {
            $this->assertInstanceOf(NotificationSystemNotFoundAliasException::class, $ex);
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
