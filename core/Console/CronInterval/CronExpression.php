<?php

namespace MkyCore\Console\CronInterval;

use Carbon\Carbon;
use MkyCore\Exceptions\Schedule\CronIntervalException;

class CronExpression
{

    const ORDER = ['minute', 'hour', 'day', 'month', 'weekDay'];

    private string $interval;
    private MinuteInterval $minute;
    private HourInterval $hour;
    private DayInterval $day;
    private MonthInterval $month;
    private WeekDayInterval $weekDay;

    /**
     * @throws CronIntervalException
     */
    public function __construct(string $interval)
    {
        if (!$this->setAllIntervals($interval)) {
            throw CronIntervalException::FormatNotValid($interval);
        }
        $this->interval = $interval;
    }

    private function setAllIntervals(string $interval): bool
    {
        $explodedInterval = explode(' ', $interval);
        if (count($explodedInterval) !== 5) {
            return false;
        }

        for ($i = 0; $i < count($explodedInterval); $i++) {
            $expression = $explodedInterval[$i];
            $result = preg_match("/^((?:[1-9]?\d|\*)\s*(?:(?:[\/-][1-9]?\d)+|(?:,[1-9]?\d)+)?\s*)$/", $expression, $matches);
            if ($matches) {
                $classInterval = 'MkyCore\Console\CronInterval\\' . ucfirst(self::ORDER[$i]) . 'Interval';
                $this->{self::ORDER[$i]} = new $classInterval($matches[0]);
            }
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function isDue(): bool
    {
        $now = now();
        $decomposition = [
            'minute' => $now->minute,
            'hour' => $now->hour,
            'day' => $now->day,
            'month' => $now->month,
            'weekDay' => $now->dayOfWeek,
        ];
        foreach ($decomposition as $name => $value) {
            if (!$this->{$name}->inTime($value)) {
                return false;
            }
        }
        return true;
    }
}