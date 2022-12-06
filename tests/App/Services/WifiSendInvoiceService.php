<?php

namespace MkyCore\Tests\App\Services;

class WifiSendInvoiceService implements InvoiceServiceInterface
{

    /**
     * @param PaymentServiceInterface $paymentService
     * @param string $ipAddress
     */
    public function __construct(private readonly PaymentServiceInterface $paymentService, private readonly string $ipAddress)
    {
    }

    public function sendInvoice(): string
    {
        $classPayment = explode('\\', get_class($this->paymentService));
        $classPayment = end($classPayment);
        $message = $classPayment." : {$this->paymentService->getTotal()}€";
        $ipAddressService = new IpAddressService($this->ipAddress, $message);
        return $ipAddressService->send();
    }
}