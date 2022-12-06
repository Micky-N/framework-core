<?php

namespace MkyCore\Tests\App\Services;

interface PaymentServiceInterface
{
    public function getTotal(): float|int;
}