<?php

namespace MkyCore\Tests\App\Services;

class IpAddressService
{

    public function __construct(private string $ipPrinter = '127.0.0.1', private string $message = '')
    {
    }


    public function send(): string
    {
        return ($this->ipPrinter != '127.0.0.1' ? 'Computer' : 'Printer') . " $this->ipPrinter: " . $this->message;
    }

    /**
     * @return string
     */
    public function getIpPrinter(): string
    {
        return $this->ipPrinter;
    }

    /**
     * @param string $ipPrinter
     */
    public function setIpPrinter(string $ipPrinter): void
    {
        $this->ipPrinter = $ipPrinter;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}