<?php

namespace MkyCore\Tests;

use MkyCore\Application;
use PHPUnit\Framework\TestCase;
use MkyCore\Config;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;

class ConfigTest extends TestCase
{

    public ?Config $config = null;

    public function setUp(): void
    {
        $this->config = new Config(new Application(__DIR__.'/App'), __DIR__.'/App/config');
    }

    /**
     * @return void
     * @throws ConfigNotFoundException
     * @throws \ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function testGetAllConfigFile(): void
    {
        $this->assertEquals([
            'test' => 123,
            'test2' => [
                'sub_test' => 2
            ]
        ], $this->config->get('test_config'));
    }

    public function testGetConfig()
    {
        $this->assertEquals(123, $this->config->get('test_config.test'));
        $this->assertEquals(['sub_test' => 2], $this->config->get('test_config.test2'));
    }

    public function testGetNestedConfig()
    {
        $this->assertEquals(2, $this->config->get('test_config.test2.sub_test'));
    }

    public function testGetExceptionIfConfigNotExists()
    {
        try {
            $this->config->get('test_config.test2.no_exist');
        }catch (\Exception $exception){
            $this->assertInstanceOf(ConfigNotFoundException::class, $exception);
        }
    }

    public function testGetDefaultValueIfConfigNotExists()
    {
        $this->assertEquals('defaultValue', $this->config->get('test_config.test2.no_exist', 'defaultValue'));
    }
}
