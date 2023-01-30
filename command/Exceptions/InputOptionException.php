<?php

namespace MkyCommand\Exceptions;

class InputOptionException extends \Exception
{
    public static function OptionNotFound(string $name): static
    {
        return new static("Option \"$name\" not found");
    }
}