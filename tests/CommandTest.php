<?php

namespace MkyCore\Tests;

use Exception;
use MkyCommand\Console;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\HelpCommand;
use MkyCommand\Input;
use MkyCore\Tests\Commands\Arguments\GreetingCommand;
use MkyCore\Tests\Commands\Arguments\NoSettingsCommand;
use MkyCore\Tests\Commands\Optional\TestOptionalCommand;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testAddCommand()
    {
        $console = new Console();
        $console->addCommand('test', new NoSettingsCommand())
            ->addCommand('greeting', new GreetingCommand());
        $this->assertCount(3, $console->getCommands());
    }

    public function testFindCommandBySignature()
    {
        $console = new Console();
        $console->addCommand('test', new NoSettingsCommand())
            ->addCommand('greeting', new GreetingCommand());
        $input = new Input(['mky', 'greeting', 'Micky']);
        $console->execute($input);
        $this->assertInstanceOf(GreetingCommand::class, $console->getCurrentCommand());
    }

    public function testNotFindCommandBySignature()
    {
        $console = new Console();
        $console->addCommand('test', new NoSettingsCommand())
            ->addCommand('greeting', new GreetingCommand());
        try {
            $input = new Input(['mky', 'wrong']);
            $console->execute($input);
        } catch (Exception $exception) {
            $this->assertInstanceOf(CommandException::class, $exception);
        }
    }

    public function testExecuteCommand()
    {
        $console = new Console();
        $console->addCommand('test', new NoSettingsCommand())
            ->addCommand('greeting', new GreetingCommand());
        $input = new Input(['mky', 'greeting', 'Micky']);
        $this->assertEquals('Micky', $console->execute($input));
    }

    public function testHelpAllCommands()
    {
        $console = new Console();
        $console->addCommand('help', new HelpCommand($console))
            ->addCommand('test:command', new NoSettingsCommand())
            ->addCommand('greet:someone', new GreetingCommand())
            ->addCommand('greet:optional', new TestOptionalCommand());
        $input = new Input(['mky', 'help']);
        $this->assertEquals(1, $console->execute($input));
    }

    public function testHelpCommand()
    {
        $console = new Console();
        $console->addCommand('test', new NoSettingsCommand())
            ->addCommand('greeting', new GreetingCommand());
        $input = new Input(['mky', 'greeting', '-h']);
        $this->assertEquals(1, $console->execute($input));
    }
}
