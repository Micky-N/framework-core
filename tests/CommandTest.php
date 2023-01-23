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
use MkyCore\Tests\Commands\Optional\ArrayCommand as OptionArrayCommand;
use MkyCore\Tests\Commands\Optional\MultiCommand as OptionMultiCommand;
use MkyCore\Tests\Commands\Optional\TestCommand as OptionalTestCommand;
use MkyCore\Tests\Commands\Optional\TestNegativeCommand;
use MkyCore\Tests\Commands\Optional\TestNoneCommand;
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
}
