<?php

namespace MkyCore\Providers;

use ReflectionException;
use MkyCore\Abstracts\ServiceProvider;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;

class RouteServiceProvider extends ServiceProvider
{

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    public function register(): void
    {
        if (in_array(\MkyCore\Facades\Config::get('app.route_mode', 'file'), ['file', 'both'])) {
            require $this->app->get('path:base').'/start/routes.php';
        }
    }
}