<?php

namespace MkyCore\Tests;

use Exception;
use MkyCommand\CommandException;
use MkyCommand\Console;
use MkyCommand\Input;
use MkyCore\Tests\Commands\Arguments\ArrayCommand;
use MkyCore\Tests\Commands\Arguments\GreetingCommand;
use MkyCore\Tests\Commands\Arguments\MultiCommand;
use MkyCore\Tests\Commands\Arguments\TestCommand;
use MkyCore\Tests\Commands\Optional\TestCommand as OptionalTestCommand;
use MkyCore\Tests\Commands\Optional\TestNotFoundCommand;
use MkyCore\Tests\Commands\Optional\TestOptionalCommand;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testAddCommand()
    {
        $console = new Console();
        $console->addCommand('test', new TestCommand())
            ->addCommand('greeting', new GreetingCommand());
        $this->assertCount(2, $console->getCommands());
    }

    public function testFindCommandBySignature()
    {
        $console = new Console();
        $console->addCommand('test', new TestCommand())
            ->addCommand('greeting', new GreetingCommand());
        $console->execute(['mky', 'greeting']);
        $this->assertInstanceOf(GreetingCommand::class, $console->getCurrentCommand());
    }

    public function testNotFindCommandBySignature()
    {
        $console = new Console();
        $console->addCommand('test', new TestCommand())
            ->addCommand('greeting', new GreetingCommand());
        try {
            $console->execute(['mky', 'wrong']);
        } catch (Exception $exception) {
            $this->assertInstanceOf(CommandException::class, $exception);
        }
    }

    public function testGetArgumentValue()
    {
        $input = new Input(['mky', 'greeting', 'Micky']);
        $greetingCommand = new GreetingCommand();
        $greetingCommand->settings();
        $greetingCommand->setRealInput($input);
        $this->assertEquals('Micky', $input->getArgument('name'));
    }

    public function testNotFoundArgumentValue()
    {
        $input = new Input(['mky', 'greeting']);
        $greetingCommand = new GreetingCommand();
        $greetingCommand->settings();
        try {
            $greetingCommand->setRealInput($input);
        } catch (Exception $exception) {
            $this->assertEquals('Argument "name" not found', $exception->getMessage());
            $this->assertInstanceOf(CommandException::class, $exception);

        }
    }

    public function testOptionalArgumentValue()
    {
        $input = new Input(['mky', 'greeting', 'Micky']);
        $greetingCommand = new GreetingCommand();
        $greetingCommand->settings();
        $greetingCommand->setRealInput($input);
        $this->assertFalse($input->getArgument('lastName'));
    }

    public function testArrayArgumentValue()
    {
        $input = new Input(['mky', 'greeting', 'Micky', 'John']);
        $greetingCommand = new ArrayCommand();
        $greetingCommand->settings();
        $greetingCommand->setRealInput($input);
        $this->assertCount(2, $input->getArgument('names'));
        $this->assertEquals(['Micky', 'John'], $input->getArgument('names'));
    }

    public function testMultiType()
    {
        $input = new Input(['mky', 'greeting', 'Micky', 'John']);
        $greetingCommand = new MultiCommand();
        $greetingCommand->settings();
        $greetingCommand->setRealInput($input);
        $this->assertEquals(['Micky', 'John'], $input->getArgument('names'));

        $input = new Input(['mky', 'greeting']);
        $greetingCommand = new MultiCommand();
        $greetingCommand->settings();
        $greetingCommand->setRealInput($input);
        $this->assertFalse($input->getArgument('names'));
    }

    public function testGetOptionValue()
    {
        $input = new Input(['mky', 'optional:test', '--name=Micky']);
        $optionalTest = new OptionalTestCommand();
        $optionalTest->settings();
        $optionalTest->setRealInput($input);
        $this->assertEquals('Micky', $input->getOption('name'));
    }

    public function testGetDefaultOptionValue()
    {
        $input = new Input(['mky', 'optional:test']);
        $optionalTest = new OptionalTestCommand();
        $optionalTest->settings();
        $optionalTest->setRealInput($input);
        $this->assertEquals('Test', $input->getOption('name'));
    }

    public function testNotFoundOptionValue()
    {
        $input = new Input(['mky', 'optional:test']);
        $greetingCommand = new TestNotFoundCommand();
        $greetingCommand->settings();
        try {
            $greetingCommand->setRealInput($input);
        } catch (Exception $exception) {
            $this->assertEquals('Option "name" not found', $exception->getMessage());
            $this->assertInstanceOf(CommandException::class, $exception);
        }
    }

    public function testOptionalOptionValue()
    {
        $input = new Input(['mky', 'optional:test', '--name']);
        $greetingCommand = new TestOptionalCommand();
        $greetingCommand->settings();
        $greetingCommand->setRealInput($input);
        $this->assertFalse($input->getOption('name'));
    }

    public function testShortNameOption()
    {

    }

    public function testMultiOption()
    {

    }

    public function testArrayOption()
    {

    }

    public function testNoneOption()
    {

    }

    public function testNegativeOption()
    {

    }

}
