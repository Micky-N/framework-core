<?php

namespace MkyCommand;

use Exception;

class CommandException extends Exception
{
    public static function CommandNotFound(string $signature): static
    {
        return new static("Command not found with signature \"$signature\"");
    }

    public static function ArgumentNotFound(string $name): static
    {
        return new static("Argument \"$name\" not found");
    }

    public static function OptionNotFound(string $name): static
    {
        return new static("Option \"$name\" not found");
    }
}