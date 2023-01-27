<?php

namespace MkyCommand;

use Closure;

class ProgressBar
{
    const NO_PROCESS = 10;
    const NO_DATA = 11;
    const PROCESS_SUCCESS = 12;
    const STOP_PROGRESSION = 13;
    const STYLES = [
        'charFull' => ['block', 'cross', 'arrow'],
        'charEmpty' => ['blank', 'block', 'underscore']
    ];

    private int $progression = 0;

    private bool $stopProgression = false;

    private string $charFull = '█';

    private string $charEmpty = '░';

    private string $blankCharEmpty = ' ';

    private string $blockCharEmpty = '░';

    private string $blockCharFull = '█';

    private string $underscoreCharEmpty = '_';

    private string $crossCharFull = 'X';

    private string $arrowCharFull = '=';

    private bool $isArrow = false;

    private bool $isDisplay = false;

    public function __construct(private readonly Output $output, private int|array $elements, private readonly ?Closure $process = null)
    {
    }

    public function start(string $startMessage = ''): static
    {
        $this->isDisplay = true;
        print $this->draw($startMessage);
        return $this;
    }

    private function draw($progressMessage = ''): string
    {
        if(!$this->isDisplay){
            return '';
        }
        $percent = round(($this->progression * 100) / $this->count());
        $bar = round(($this->count() * $percent) / 100);
        return sprintf("\r%s%%[%s" . ($this->isArrow ? '>' : '') . "%s] %s/%s %s", $percent, str_repeat($this->charFull, $bar), str_repeat($this->charEmpty, $this->count() - $bar), $this->progression, $this->count(), $progressMessage);
    }

    public function setArrowBar(bool $setArrow = true): static
    {
        $this->setCharFull('arrow');
        $this->setCharEmpty('blank');
        $this->isArrow($setArrow);
        return $this;
    }

    public function setBlockBar(): static
    {
        $this->setCharFull('block');
        $this->setCharEmpty('block');
        $this->isArrow(false);
        return $this;
    }

    public function setCharFull(string $style): static
    {
        if (!$this->styleIsValid($style)) {
            $style = 'block';
        }

        $this->charFull = $this->{$style . 'CharFull'};
        return $this;
    }

    public function setCharEmpty(string $style): static
    {
        if (!$this->styleIsValid($style, 'charEmpty')) {
            $style = 'block';
        }

        $this->charEmpty = $this->{$style . 'CharEmpty'};
        return $this;
    }

    public function isArrow(bool $isArrow): static
    {
        $this->isArrow = $isArrow;
        return $this;
    }

    private function styleIsValid(string $style, string $char = 'charFull'): bool
    {
        return in_array($style, self::STYLES[$char]);
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
            if (!$this->isStopped()) {
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
        if($this->isStopped()){
            return $this;
        }
        $this->progression++;

        print $this->draw($progressMessage);

        return $this;
    }

    public function reset(): static
    {
        $this->progression = 0;
        return $this;
    }

    public function finish(string $finishMessage = ''): static
    {
        $this->reset();
        echo "\n";
        if($finishMessage){
            echo $finishMessage . "\n";
        }
        return $this;
    }

    /**
     * @return array|int
     */
    public function getElements(): array|int
    {
        return $this->elements;
    }

    public function stop(string $stopMessage = ''): static
    {
        $this->stopProgression = true;
        if($stopMessage){
            echo "\n".$stopMessage . "\n";
        }
        return $this;
    }

    public function resume(string $resumeMessage = ''): static
    {
        $this->stopProgression = false;
        if($resumeMessage){
            echo $resumeMessage . "\n";
        }
        return $this;
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

    /**
     * @param int|string $index
     * @param Closure $resolve
     * @return static
     * 
     */
    public function resolve(int|string $index, Closure $resolve, string $resolveMessage = ''): static
    {
        $this->elements[$index] = $resolve($this->elements[$index], $index, $this);
        if ($resolveMessage) {
            echo "\n".$resolveMessage . "\n";
        }
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->progression === $this->count();
    }
}
