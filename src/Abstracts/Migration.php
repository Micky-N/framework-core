<?php

namespace MkyCore\Abstracts;

class Migration
{
    protected int $priority = 1;

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}