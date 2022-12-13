<?php

namespace MkyCore\Providers;

use MkyCore\Abstracts\ServiceProvider;
use MkyCore\Application;
use MkyCore\Config;
use MkyCore\Container;
use MkyCore\Database;
use MkyCore\FileManager;
use MkyCore\Request;
use MkyCore\Response;
use MkyCore\Router\Router;
use MkyCore\Session;
use MkyCore\View\Compile;
use Psr\Http\Message\ResponseInterface;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {

        $this->app->singleton(Router::class, function (Container $container) {
            $router = new Router($container->getInstance(Application::class));
            $router->getRoutesFromAnnotation($container->get('path:app'));
            return $router;
        });

        $this->app->singleton(Request::class, fn() => Request::fromGlobals());

        $this->app->singleton(ResponseInterface::class, function () {
            return new Response();
        });

        $this->app->singleton(Session::class, function (Container $container, array $options = []) {
            return new Session($options);
        });

        $this->app->singleton(Config::class, function (Container $container) {
            return new Config($container->getInstance(Application::class), $container->get('path:config'));
        });

        $this->app->singleton(FileManager::class, function (Container $container) {
            $space = $container->get(Config::class)->get('filesystems.default', 'public');
            $fsConfig = $container->get(Config::class)->get('filesystems.spaces.' . $space);
            return new FileManager($space, $fsConfig);
        });
        
        $this->app->singleton(Database::class, function(Container $container){
            $system = $container->get(Config::class)->get('database.default', 'mysql');
            $configDB = array_merge($container->get(Config::class)->get('database.connections.' . $system), compact('system'));
            return new Database($configDB);
        });
    }
}