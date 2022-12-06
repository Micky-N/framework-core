<?php

namespace MkyCore\Tests\App\Services;

class StripeService implements PaymentServiceInterface
{
    public function __construct(private readonly int|float $amount = 0, private readonly int $quantity = 0)
    {
    }

    public function getTotal(): float|int
    {
        return $this->amount * $this->quantity;
    }
}