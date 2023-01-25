<?php

namespace MkyCommand\Exceptions;

class SectionException extends \Exception
{

    public static function SectionNotFound(string $name): static
    {
        return new static("Section '$name' not found");
    }
}