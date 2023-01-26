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

    public function progress(int $progress)
    {

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
}