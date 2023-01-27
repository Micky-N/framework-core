<?php

namespace MkyCore\Tests;

use Exception;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input;
use MkyCore\Tests\Commands\Arguments\ArrayCommand;
use MkyCore\Tests\Commands\Arguments\GreetingCommand;
use MkyCore\Tests\Commands\Arguments\MultiCommand;
use MkyCore\Tests\Commands\Arguments\NoSettingsCommand;
use MkyCore\Tests\Commands\Optional\ArrayCommand as OptionArrayCommand;
use MkyCore\Tests\Commands\Optional\MultiCommand as OptionMultiCommand;
use MkyCore\Tests\Commands\Optional\TestCommand as OptionalTestCommand;
use MkyCore\Tests\Commands\Optional\TestNegativeCommand;
use MkyCore\Tests\Commands\Optional\TestNoneCommand;
use MkyCore\Tests\Commands\Optional\TestNotFoundCommand;
use MkyCore\Tests\Commands\Optional\TestOptionalCommand;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{

    public function testNoSettings()
    {
        $input = new Input(['mky', 'greeting', 'Micky']);
        $command = new NoSettingsCommand();
        $command->settings();
        $command->setRealInput($input);
        try{
            $this->assertEquals('Micky', $input->argument('name'));
        }catch(Exception $exception){
            $this->assertEquals('Argument "name" not found', $exception->getMessage());
            $this->assertInstanceOf(CommandException::class, $exception);
        }
    }

    public function testGetArgumentValue()
    {
        $input = new Input(['mky', 'greeting', 'Micky']);
        $command = new GreetingCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertEquals('Micky', $input->argument('name'));
    }

    public function testNotFoundArgumentValue()
    {
        $input = new Input(['mky', 'greeting']);
        $command = new GreetingCommand();
        $command->settings();
        try {
            $command->setRealInput($input);
        } catch (Exception $exception) {
            $this->assertEquals('Argument "name" not found', $exception->getMessage());
            $this->assertInstanceOf(CommandException::class, $exception);

        }
    }

    public function testOptionalArgumentValue()
    {
        $input = new Input(['mky', 'greeting', 'Micky']);
        $command = new GreetingCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertFalse($input->argument('lastName'));
    }

    public function testArrayArgumentValue()
    {
        $input = new Input(['mky', 'greeting', 'Micky', 'John']);
        $command = new ArrayCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertCount(2, $input->argument('names'));
        $this->assertEquals(['Micky', 'John'], $input->argument('names'));
    }

    public function testMultiType()
    {
        $input = new Input(['mky', 'greeting', 'Micky', 'John']);
        $command = new MultiCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertEquals(['Micky', 'John'], $input->argument('names'));

        $input = new Input(['mky', 'greeting']);
        $command = new MultiCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertFalse($input->argument('names'));
    }

    public function testGetOptionValue()
    {
        $input = new Input(['mky', 'optional:test', '--name=Micky']);
        $optionalTest = new OptionalTestCommand();
        $optionalTest->settings();
        $optionalTest->setRealInput($input);
        $this->assertEquals('Micky', $input->option('name'));
    }

    public function testGetDefaultOptionValue()
    {
        $input = new Input(['mky', 'optional:test']);
        $optionalTest = new OptionalTestCommand();
        $optionalTest->settings();
        $optionalTest->setRealInput($input);
        $this->assertEquals('Test', $input->option('name'));
    }

    public function testNotFoundOptionValue()
    {
        $input = new Input(['mky', 'optional:test']);
        $command = new TestNotFoundCommand();
        $command->settings();
        try {
            $command->setRealInput($input);
        } catch (Exception $exception) {
            $this->assertEquals('Option "name" not found', $exception->getMessage());
            $this->assertInstanceOf(CommandException::class, $exception);
        }
    }

    public function testOptionalOptionValue()
    {
        $input = new Input(['mky', 'optional:test', '--name']);
        $command = new TestOptionalCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertFalse($input->option('name'));
    }

    public function testShortNameOption()
    {
        $input = new Input(['mky', 'optional:test', '-n', 'Micky']);
        $optionalTest = new OptionalTestCommand();
        $optionalTest->settings();
        $optionalTest->setRealInput($input);
        $this->assertEquals('Micky', $input->option('name'));

        $input = new Input(['mky', 'optional:test', '-nMicky']);
        $optionalTest = new OptionalTestCommand();
        $optionalTest->settings();
        $optionalTest->setRealInput($input);
        $this->assertEquals('Micky', $input->option('name'));
    }

    public function testArrayOption()
    {
        $input = new Input(['mky', 'greeting', '--names=Micky', '--names=John']);
        $command = new OptionArrayCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertCount(2, $input->option('names'));
        $this->assertEquals(['Micky', 'John'], $input->option('names'));

        $input = new Input(['mky', 'greeting', '-nMicky', '-nJohn']);
        $command = new OptionArrayCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertCount(2, $input->option('names'));
        $this->assertEquals(['Micky', 'John'], $input->option('names'));
    }

    public function testMultiTypeOption()
    {
        $input = new Input(['mky', 'greeting', '--names=Micky']);
        $command = new OptionMultiCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertEquals(['Micky'], $input->option('names'));

        $input = new Input(['mky', 'greeting', '--names']);
        $command = new OptionMultiCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertEquals(['Test'], $input->option('names'));
    }

    public function testNoneOption()
    {
        $input = new Input(['mky', 'optional:test', '--name']);
        $command = new TestNoneCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertTrue($input->option('name'));

        $input = new Input(['mky', 'optional:test', '-n']);
        $command = new TestNoneCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertTrue($input->option('name'));

        $input = new Input(['mky', 'optional:test']);
        $command = new TestNoneCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertFalse($input->option('name'));
    }

    public function testNegativeOption()
    {
        $input = new Input(['mky', 'optional:test']);
        $command = new TestNegativeCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertNull($input->option('name'));

        $input = new Input(['mky', 'optional:test', '--name']);
        $command = new TestNegativeCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertTrue($input->option('name'));

        $input = new Input(['mky', 'optional:test', '--no-name']);
        $command = new TestNegativeCommand();
        $command->settings();
        $command->setRealInput($input);
        $this->assertFalse($input->option('name'));
    }
}
