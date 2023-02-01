<?php

namespace MkyCore\Exceptions\Schedule;

class CronIntervalException extends \Exception
{
    public static function FormatNotValid(string $interval): static
    {
        return new static("The format '$interval' is not valid");
    }

    public static function IntervalNotCorrect(int $min, int $max): static
    {
        return new static("The minimum '$min' must be before the maximum '$max'");
    }

    public static function FrequencyNotCorrect(int $frequency): static
    {
        return new static("Frecency '$frequency' not correct");
    }

    public static function SingleValueNotCorrect(int $value): static
    {
        return new static("Value '$value' not correct");
    }

    public static function IsNotInterval(string $expression): static
    {
        return new static("Expression must be an interval format '$expression'");
    }
}