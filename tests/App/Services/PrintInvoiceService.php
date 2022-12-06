<?php

namespace MkyCore\Tests\App\Services;

class PrintInvoiceService implements InvoiceServiceInterface
{
    public function __construct(private readonly PaymentServiceInterface $paymentService, private readonly IpAddressService $ipAddressService)
    {
    }

    public function sendInvoice(): string
    {
        $classPayment = explode('\\', get_class($this->paymentService));
        $classPayment = end($classPayment);
        $message = $classPayment." : {$this->paymentService->getTotal()}€";
        $this->ipAddressService->setMessage($message);
        return $this->ipAddressService->send();
    }
}