<?php

namespace MkyCore\Providers;

use MkyCore\Config;
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
        $modules = $this->app->getModules();
        foreach ($modules as $name => $module){
            $modulePath = $this->app->getModuleKernel($name)->getModulePath();
            if (in_array($this->app->get(Config::class)->get($name.'::app.route_mode', 'file'), ['file', 'both'])) {
                if($name == 'root'){
                    require $this->app->get('path:base').'/start/routes.php';
                }else{
                    if(file_exists($modulePath.'/start/routes.php')){
                        require $modulePath.'/start/routes.php';
                    }
                }
            }
        }
    }
}