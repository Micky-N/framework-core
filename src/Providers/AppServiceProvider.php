<?php

namespace MkyCore\Providers;

use Exception;
use MkyCore\Abstracts\ServiceProvider;
use MkyCore\Application;
use MkyCore\Config;
use MkyCore\Container;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\FileManager;
use MkyCore\Interfaces\ViewCompileInterface;
use MkyCore\Request;
use MkyCore\Response;
use MkyCore\Router\Router;
use MkyCore\Session;
use MkyCore\View;
use MkyCore\View\Compile;
use MkyCore\View\TwigCompile;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

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
            $space = \MkyCore\Facades\Config::get('filesystems.default', 'public');
            $fsConfig = \MkyCore\Facades\Config::get('filesystems.spaces.' . $space);
            return new FileManager($space, $fsConfig);
        });
    }
}