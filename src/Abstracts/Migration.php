<?php

namespace MkyCore\Abstracts;

class Migration
{
    protected int $order = 1;

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }
}