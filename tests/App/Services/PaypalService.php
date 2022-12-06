<?php

namespace MkyCore\Tests\App\Services;

class PaypalService implements PaymentServiceInterface
{
    public function __construct(private readonly int|float $amount, private readonly int $quantity)
    {
    }

    public function getTotal(): float|int
    {
        return $this->amount * $this->quantity;
    }
}