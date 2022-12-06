<?php

namespace MkyCore\Providers;

use Exception;
use MkyCore\Interfaces\ViewCompileInterface;
use MkyCore\View\TwigCompile;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use MkyCore\Abstracts\ServiceProvider;
use MkyCore\Application;
use MkyCore\Config;
use MkyCore\Container;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Request;
use MkyCore\Response;
use MkyCore\Router\Router;
use MkyCore\Session;
use MkyCore\View\Compile;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {

        $this->app->singleton(Router::class, function (Container $container) {
            $router = new Router($container->getInstance(Application::class));
            if (in_array(\MkyCore\Facades\Config::get('app.route_mode', 'file'), ['controller', 'both'])) {
                $router->getRoutesFromAnnotation($container->get('path:app'));
            }
            return $router;
        });

        $this->app->singleton(Request::class, function () {
            return Request::fromGlobals();
        });

        $this->app->singleton(ResponseInterface::class, function () {
            return new Response();
        });

        $this->app->singleton(Session::class, function (Container $container, array $options = []) {
            return new Session($options);
        });

        $this->app->singleton(Config::class, function (Container $container) {
            return new Config($container->get('path:config'));
        });
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws Exception
     */
    public function viewCompile(): ViewCompileInterface
    {
        $base = $this->app->get('path:base');
        return new TwigCompile(\MkyCore\Facades\Config::get('views.twig', [
            'template' => $base . '/views',
            'options' => [
                'cache' => $base . '/cache/views'
            ]
        ]));
    }
}