<?php

namespace MkyCommand;

use Closure;

class ProgressBar
{
    private int $complete = 0;
    private bool $stopProgression = false;

    public function __construct(private readonly int|array $elements, private readonly ?Closure $process = null)
    {
    }

    public function start(string $startMessage = '')
    {
        echo $startMessage . "\n";
    }

    public function progress(string $progressMessage = '')
    {
        $this->complete++;
        $perc = round(($this->complete * 100) / $this->count());
        $bar = round(($this->count() * $perc) / 100);
        echo sprintf("%s%%[%s>%s] %s/%s %s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $this->count() - $bar), $this->complete, $this->count(), $progressMessage) . "\n";
    }

    public function process()
    {
        if (!$this->process) {
            exit('No process to run');
        }

        if(!is_array($this->elements)){
            exit('No data to fetch');
        }
        $i = 0;
        foreach ($this->elements as $index => $element) {
            if($this->complete && $i < $this->complete){
                $i++;
                continue;
            }
            if(!$this->stopProgression){
                $i++;
                $process = $this->process;
                $response = $process($element, $index, $this);
                $this->progress($response);
            }else{
                break;
            }
        }
        if($this->complete === $this->count() - 1){
            $this->reset();
        }
    }

    public function completed(string $endMessage = '')
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

    public function count(): int
    {
        if (is_array($this->elements)) {
            return count($this->elements);
        }
        return $this->elements;
    }

    public function reset()
    {
        $this->complete = 0;
    }

    public function stop(string $stopMessage = '')
    {
        $this->stopProgression = true;
        echo $stopMessage ? $stopMessage. "\n" : '';
    }

    public function resume(string $resumeMessage = '')
    {
        $this->stopProgression = true;
        echo $resumeMessage ? $resumeMessage. "\n" : '';
    }
}
