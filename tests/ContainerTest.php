<?php

namespace MkyCore\Tests;

use MkyCore\Container;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Tests\App\Services\InvoiceServiceInterface;
use MkyCore\Tests\App\Services\MailInvoiceService;
use MkyCore\Tests\App\Services\MailerService;
use MkyCore\Tests\App\Services\PaymentServiceInterface;
use MkyCore\Tests\App\Services\PaypalService;
use MkyCore\Tests\App\Services\PrintInvoiceService;
use MkyCore\Tests\App\Services\StripeService;
use MkyCore\Tests\App\Services\WifiSendInvoiceService;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    public function setUp(): void
    {
        $this->container = new Container();
    }

    public function testGetContainer()
    {
        $this->container->bind(PaypalService::class, fn() => new PaypalService(2, 5));
        $this->assertTrue($this->container->has(PaypalService::class));
        $this->assertEquals(10, $this->container->get(PaypalService::class)->getTotal());
    }

    public function testExceptionNotFoundContainer()
    {
        try {
            $this->container->get(PaypalService::class);
        } catch (\Exception $exception){
            $this->assertInstanceOf(FailedToResolveContainerException::class, $exception);
        }
    }

    public function testConstructWithContainer()
    {
        $this->container->bind(PaypalService::class, fn() => new PaypalService(5.2, 4));
        $this->container->bind(MailerService::class, fn() => new MailerService('test@test.test'));

        $this->container->bind(MailInvoiceService::class, function(Container $c){
            return new MailInvoiceService($c->get(PaypalService::class), $c->get(MailerService::class));
        });

        $this->assertEquals("Message envoyé: PaypalService : 20.8€", $this->container->get(MailInvoiceService::class)->sendInvoice());
    }

    public function testAutoWiring()
    {
        $this->container->bind(PaypalService::class, fn() => new PaypalService(5.2, 4));
        $this->container->bind(MailerService::class, fn() => new MailerService('test@test.test'));

        $this->assertInstanceOf(MailInvoiceService::class, $this->container->get(MailInvoiceService::class));
        $this->assertEquals("Message envoyé: PaypalService : 20.8€", $this->container->get(MailInvoiceService::class)->sendInvoice());
    }

    public function testAutoWiringWithInterface()
    {
        $this->container->bind(PaymentServiceInterface::class, StripeService::class);
        $this->container->bind(InvoiceServiceInterface::class, PrintInvoiceService::class);

        $this->assertInstanceOf(PrintInvoiceService::class, $this->container->get(InvoiceServiceInterface::class));
        $this->assertEquals("Printer 127.0.0.1: StripeService : 0€", $this->container->get(InvoiceServiceInterface::class)->sendInvoice());
    }

    public function testGetWithOneRegisterParameterAndOneRequiredParameterContainer()
    {
        $ipAddress = long2ip(rand(0, 4294967295));
        $this->container->bind(PaymentServiceInterface::class, fn() => new StripeService(1.5, 2));
        $this->container->bind(InvoiceServiceInterface::class, function(Container $container) use ($ipAddress){
            return new WifiSendInvoiceService($container->get(PaymentServiceInterface::class), $ipAddress);
        });

        $this->assertInstanceOf(WifiSendInvoiceService::class, $this->container->get(InvoiceServiceInterface::class));
        $this->assertEquals("Computer $ipAddress: StripeService : 3€", $this->container->get(InvoiceServiceInterface::class)->sendInvoice());
    }

    public function testGetContainerWithOutParams()
    {
        $ipAddressComputer = long2ip(rand(0, 4294967295));
        $this->container->bind(PaymentServiceInterface::class, fn() => new StripeService(1.5, 2));
        $this->container->bind(InvoiceServiceInterface::class, function(Container $container, array $options){
            return new WifiSendInvoiceService($container->get(PaymentServiceInterface::class), $options['ipAddressComputer']);
        });

        $this->assertEquals("Computer $ipAddressComputer: StripeService : 3€", $this->container->get(InvoiceServiceInterface::class, compact('ipAddressComputer'))->sendInvoice());
    }
}
