<?php

namespace MkyCommand\Exceptions;

use Exception;

class CommandException extends Exception
{
    public static function CommandNotFound(string $signature): static
    {
        return new static("Command not found with signature \"$signature\"");
    }
}