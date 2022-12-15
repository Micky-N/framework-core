<?php

namespace MkyCore\Abstracts;


use MkyCore\Application;

abstract class ServiceProvider
{
    public function __construct(protected Application $app)
    {
        
    }

    public function register(): void
    {
        //
    }
}