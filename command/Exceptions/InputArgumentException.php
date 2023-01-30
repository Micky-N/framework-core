<?php

namespace MkyCommand\Exceptions;

class InputArgumentException extends \Exception
{
    public static function ArgumentNotFound(string $name): static
    {
        return new static("Argument \"$name\" not found");
    }
}