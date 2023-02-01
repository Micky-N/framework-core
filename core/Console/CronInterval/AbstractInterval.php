<?php

namespace MkyCore\Console\CronInterval;

use MkyCore\Exceptions\Schedule\CronIntervalException;

abstract class AbstractInterval
{
    static protected int $_MIN;
    static protected int $_MAX;

    protected ?int $min = null;
    protected ?int $max = null;
    protected ?int $frequency = null;
    protected ?array $list = null;
    protected string $expression;

    /**
     * @throws CronIntervalException
     */
    public function __construct(string $expression)
    {
        if (preg_match('/^((\d+|\*)?\s*(-(\d+?))*)\s*(\/(\d+?))*$/', $expression, $matches)) {
            $matches = $this->isSingleValue($matches);
            $matches = $this->isIntervalValue($matches);
            $matches = $this->isFrequency($matches);
        } elseif (str_contains($expression, ',')) {
            if (str_contains($expression, '*')) {
                throw CronIntervalException::FormatNotValid($expression);
            }

            $values = explode(',', $expression);
            $values = array_map(fn($val) => (int)trim($val), $values);
            $values = array_filter($values, fn($val) => static::$_MIN <= $val && $val <= static::$_MAX);
            $values = array_unique($values);
            sort($values, SORT_NATURAL);
            $this->list = $values;
        }
        $this->expression = $expression;
    }

    /**
     * @param array $matches
     * @return array
     * @throws CronIntervalException
     */
    protected function isSingleValue(array $matches = []): array
    {
        if (isset($matches[2]) && $matches[2] !== '') {
            if ($matches[2] === '*') {
                $this->min = static::$_MIN;
                $this->max = static::$_MAX;
            } else {
                if (!$this->checkSingleInterval($matches[2])) {
                    throw CronIntervalException::SingleValueNotCorrect((int)$matches[2]);
                }
                $this->min = $this->max = (int)$matches[2];
            }
        }
        return $matches;
    }

    public function checkSingleInterval(string $value): bool
    {
        if ($value < static::$_MIN || static::$_MAX < $value) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $matches
     * @return mixed
     * @throws CronIntervalException
     */
    protected function isIntervalValue(mixed $matches): mixed
    {
        if (isset($matches[4]) && $matches[4] !== '') {
            if (!$this->checkInterval($matches[4])) {
                throw CronIntervalException::IntervalNotCorrect((int)$matches[4], (int)$matches[2]);
            }
            $this->max = (int)$matches[4];
        }
        return $matches;
    }

    public function checkInterval(string $value): bool
    {
        if ($value < static::$_MIN || static::$_MAX < $value) {
            return false;
        }
        if ($this->min > $value) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $matches
     * @return mixed
     * @throws CronIntervalException
     */
    protected function isFrequency(mixed $matches): mixed
    {
        if (isset($matches[6]) && $matches[6] !== '') {
            if ($this->max === $this->min) {
                throw CronIntervalException::IsNotInterval($matches[0]);
            }
            if (!$this->checkFrequency($matches[6]) || !isset($matches[4])) {
                throw CronIntervalException::FrequencyNotCorrect((int)$matches[6]);
            }
            $this->frequency = (int)$matches[6];
        }
        return $matches;
    }

    public function checkFrequency(string $value): bool
    {
        if ($value < 2 || $this->max <= $value) {
            return false;
        }
        return true;
    }

    public function inTime(int $value): bool
    {
        if (!is_null($this->min) && $this->min === $this->max) {
            return $this->isSame($value);
        } elseif ($this->min !== $this->max) {
            return $this->isBetween($value);
        } elseif ($this->list) {
            return $this->inList($value);
        }
        return false;
    }

    protected function isSame(int $value): bool
    {
        return $this->min === $value;
    }

    protected function isBetween(int $value): bool
    {
        if (!$this->frequency) {
            return $this->min <= $value && $value <= $this->max;
        } else {
            return in_array($value, range($this->min, $this->max, $this->frequency));
        }
    }

    protected function inList(int $value): bool
    {
        return in_array($value, $this->list);
    }

    public function next(int $value): array
    {
        $next = -1;
        if ($this->min <= $value && $value <= $this->max) {
            $range = range($this->min, $this->max, $this->frequency ?? 1);
            $in_array = array_filter($range, fn($int) => $int > $value);
            $value = reset($in_array);
            if($value === false){
                $value = $this->min;
                $next += 0;
            }
        } else {
            if ($value > $this->max) {
                $next += 0;
            }
            $value = $this->min;
        }
        return compact('value', 'next');
    }
    
    public function expression(): string
    {
        return $this->expression;
    }
    
    public function range(): array
    {
        return range($this->min, $this->max, $this->frequency ?? 1);
    }
}