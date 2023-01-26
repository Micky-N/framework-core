<?php

namespace MkyCommand;

use Closure;

class ProgressBar
{
    const NO_PROCESS = 10;
    const NO_DATA = 11;
    const PROCESS_SUCCESS = 12;
    const STOP_PROGRESSION = 13;

    private int $progression = 0;
    private bool $stopProgression = false;

    public function __construct(private int|array $elements, private readonly ?Closure $process = null)
    {
    }

    public function start(string $startMessage = ''): void
    {
        print $this->draw();
    }

    private function draw($progressMessage = ''): string
    {
        $percent = round(($this->progression * 100) / $this->count());
        $bar = round(($this->count() * $percent) / 100);
        return sprintf("\r%s%%[%s>%s] %s/%s %s", $percent, str_repeat("=", $bar), str_repeat(" ", $this->count() - $bar), $this->progression, $this->count(), $progressMessage);
    }

    public function count(): int
    {
        if (is_array($this->elements)) {
            return count($this->elements);
        }
        return $this->elements;
    }

    public function process(): int
    {
        if (!$this->process) {
            return self::NO_PROCESS;
        }

        if (!is_array($this->elements)) {
            return self::NO_DATA;
        }
        $i = 0;
        foreach ($this->elements as $index => $element) {
            if ($this->progression && $i < $this->progression) {
                $i++;
                continue;
            }
            if (!$this->stopProgression) {
                $i++;
                $process = $this->process;
                $response = $process($element, $index, $this);
                $this->progress($response);
            } else {
                break;
            }
        }
        if ($this->progression === $this->count() - 1) {
            $this->reset();
        }
        return $this->stopProgression ? self::STOP_PROGRESSION : self::PROCESS_SUCCESS;
    }

    public function progress(string $progressMessage = ''): static
    {
        $this->progression++;

        print $this->draw($progressMessage);

        return $this;
    }

    public function reset(): void
    {
        $this->progression = 0;
    }

    public function completed(string $endMessage = ''): void
    {
        $this->reset();
        echo $endMessage . "\n";
    }

    /**
     * @return array|int
     */
    public function getElements(): array|int
    {
        return $this->elements;
    }

    public function stop(string $stopMessage = ''): void
    {
        $this->stopProgression = true;
        if ($stopMessage) {
            echo $stopMessage . "\n";
        }
    }

    public function resume(string $resumeMessage = ''): void
    {
        $this->stopProgression = false;
        if ($resumeMessage) {
            echo $resumeMessage . "\n";
        }
    }

    public function isStopped(): bool
    {
        return $this->stopProgression;
    }

    /**
     * @param bool|Closure $real
     * @return mixed
     */
    public function current(bool|Closure $real = false): mixed
    {
        if ($real) {
            if (is_bool($real)) {
                return array_keys($this->elements)[$this->progression];
            } elseif (is_callable($real)) {
                return $real($this->elements[$this->progression], $this->progression);
            }
        }
        return $this->progression;
    }

    public function resolve(int|string $index, Closure $resolve, string $resolveMessage = ''): void
    {
        $this->elements[$index] = $resolve($this->elements[$index], $index, $this);
        if ($resolveMessage) {
            echo $resolveMessage . "\n";
        }
    }

    public function isCompleted(): bool
    {
        return $this->progression === $this->count();
    }

    public function __toString()
    {
        return $this->draw();
    }
}
