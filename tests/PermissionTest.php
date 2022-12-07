<?php

namespace MkyCore\Tests;


use MkyCore\Application;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Permission;
use MkyCore\Router\Router;
use MkyCore\Tests\Entities\Test;
use MkyCore\Tests\Entities\UserTest;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{

    /**
     * @var Permission
     */
    private Permission $permission;
    private Router $router;

    /**
     * @return void
     * @throws \ReflectionException
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function setUp(): void
    {
        $this->permission = new Permission();
        $app = new Application(__DIR__ . DIRECTORY_SEPARATOR . 'App');
        $this->router = new Router($app);
    }

    public function testWithTrueGate()
    {
        $this->permission->define('test', fn(UserTest $userTest, Test $test) => true);
        $user = new UserTest();
        $test = new Test();
        $this->assertTrue($this->permission->authorize('test', $user, $test));
    }

    public function testWithFalseGate()
    {
        $this->permission->define('test', fn(UserTest $userTest, Test $test) => false);
        $user = new UserTest();
        $test = new Test();
        $this->assertFalse($this->permission->authorize('test', $user, $test));
    }

    public function testWithTrueConditionGate()
    {
        $this->permission->define('test', fn(UserTest $userTest, Test $test) => $test->user_id === $userTest->id);
        $user = new UserTest();
        $test = new Test();
        $test->user_id = 7;
        $this->assertTrue($this->permission->authorize('test', $user, $test));
    }

    public function testWithFalseConditionGate()
    {
        $this->permission->define('test', fn(UserTest $userTest, Test $test) => $test->user_id === $userTest->id);
        $user = new UserTest();
        $test = new Test();
        $test->user_id = 1;
        $this->assertFalse($this->permission->authorize('test', $user, $test));
    }
}
