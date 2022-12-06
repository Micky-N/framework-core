<?php

namespace MkyCore\Tests\App\Services;

interface InvoiceServiceInterface
{
    /**
     * @return string
     */
    public function sendInvoice(): string;
}