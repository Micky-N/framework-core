<?php

namespace MkyCore\Tests\App\Providers;

use MkyCore\Abstracts\ServiceProvider;
use MkyCore\Tests\App\Services\PaymentServiceInterface;
use MkyCore\Tests\App\Services\PaypalService;

class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {
        $this->app->bind(PaymentServiceInterface::class, PaypalService::class);
    }
}