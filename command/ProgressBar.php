<?php

namespace MkyCommand;

class ProgressBar
{
    private int $complete = 0;

    public function __construct(private readonly int $size)
    {
    }

    public function display()
    {

    }

    public function progress()
    {
        $perc = round((25 * 100) / 50);
        $bar = round((50 * $perc) / 100);
        return sprintf("%s%[%s>%s] %s/%s %s\r", $perc, str_repeat("=", $bar), str_repeat(" ", 50 - $bar), 50, 50, 'tes');
    }

    public function completed()
    {

    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    function progress_bar($done, $total, $info = "", $width = 50)
    {

    }
}