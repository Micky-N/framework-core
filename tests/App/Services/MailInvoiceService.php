<?php

namespace MkyCore\Tests\App\Services;

class MailInvoiceService implements InvoiceServiceInterface
{
    public function __construct(private readonly PaypalService $paymentService, private readonly MailerService $mailerService, private int $number = 0)
    {
    }

    public function sendInvoice(): string
    {
        $classPayment = explode('\\', get_class($this->paymentService));
        $classPayment = end($classPayment);
        $message = $classPayment." : {$this->paymentService->getTotal()}€".($this->number ? " en {$this->number} fois" : '');
        $this->mailerService->setMessage($message);
        return $this->mailerService->send();
    }
}