<?php

namespace MkyCommand;

class ConsoleException extends \Exception
{
    public static function CommandNotExtendsAbstract(string $command): static
    {
        return new static("Command $command must extend the AbstractCommand class");
    }

    public static function CommandNotFound(string $command): static
    {
        return new static("Command class $command not found");
    }
}