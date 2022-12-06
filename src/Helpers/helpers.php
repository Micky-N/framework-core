<?php


use MkyCore\Application;
use MkyCore\AuthManager;
use MkyCore\Config;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Middlewares\CsrfMiddleware;
use MkyCore\Request;
use MkyCore\Session;
use Carbon\Carbon;
use MkyCore\Facades\Router;
use MkyCore\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use MkyCore\Facades\View;

if(!function_exists('session')){
    /**
     * @param string|null $key
     * @param mixed|null $value
     * @return mixed|Session|null
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    function session(string $key = null, mixed $value = null): mixed
    {
        $session = app()->get(Session::class);
        if($key){
            if($value){
                $session->set($key, $value);
            }
            return $session->get($key) ?? null;
        }
        return $session;
    }
}

if(!function_exists('request')){
    function request(): ServerRequestInterface
    {
        return Request::fromGlobals();
    }
}

if(!function_exists('route')){
    function route(string $name = null, array $params = []): string|Router
    {
        if($name){
            return Router::getUrlFromName($name, $params);
        }
        return new Router();
    }
}

if(!function_exists('view')){
    /**
     * @param string $view
     * @param array $params
     * @return \MkyCore\View
     */
    function view(string $view, array $params = []): \MkyCore\View
    {
        return View::render($view, $params);
    }
}

if(!function_exists('app')){
    function app(): Application
    {
        return Application::getBaseInstance();
    }
}

if(!function_exists('auth')){
    /**
     * @return AuthManager
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    function auth(): AuthManager
    {
        return app()->get(AuthManager::class);
    }
}

if(!function_exists('redirect')){
    function redirect(string $to = null, int $status = 302): RedirectResponse
    {
        $redirect = new RedirectResponse();
        if($to){
            return $redirect->to($to, $status);
        }
        return $redirect;
    }
}

if(!function_exists('now')){
    function now(DateTimeZone|null|string $tz = 'Europe/Paris'): Carbon
    {
        return Carbon::now($tz);
    }
}

if(!function_exists('config')){
    /**
     * @param string $key
     * @param string|null $default
     * @return mixed
     * @throws Exception
     */
    function config(string $key, mixed $default = null): mixed
    {
        $config = app()->get(Config::class);
        return $config->get($key, $default);
    }
}

if(!function_exists('env')){
    /**
     * @param string $key
     * @param string|null $default
     * @return string|array|bool|null
     */
    function env(string $key, string $default = null): string|array|bool|null
    {
        return getenv($key) ?? $default;
    }
}

if(!function_exists('csrf')){
    /**
     * @return string
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    function csrf(): string
    {
        $token = app()->get(CsrfMiddleware::class)->generateToken();
        $name = CsrfMiddleware::FORM_KEY;
        return "<input type='hidden' name='$name' value='$token' />";
    }
}