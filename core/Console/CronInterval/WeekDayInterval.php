<?php

namespace MkyCore\Console\CronInterval;

class WeekDayInterval extends AbstractInterval
{
    static protected int $_MIN = 0;
    static protected int $_MAX = 7;

    protected function isBetween(int $value): bool
    {
        if($this->max == 7 && $value == 0){
            $value = 7;
        }
        return parent::isBetween($value);
    }
}