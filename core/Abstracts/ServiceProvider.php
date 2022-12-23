<?php

namespace MkyCore\Abstracts;


use MkyCore\Application;

abstract class ServiceProvider
{
    public function __construct(protected Application $app)
    {
        
    }

    /**
     * This method will be call to Application class
     *
     * @return void
     */
    public function register(): void
    {
        //
    }
}