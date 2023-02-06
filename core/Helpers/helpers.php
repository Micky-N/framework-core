<?php


use Carbon\Carbon;
use MkyCore\Application;
use MkyCore\AuthManager;
use MkyCore\Config;
use MkyCore\Facades\View;
use MkyCore\JsonResponse;
use MkyCore\Middlewares\CsrfMiddleware;
use MkyCore\RedirectResponse;
use MkyCore\Request;
use MkyCore\Router\Router;
use MkyCore\Session;
use Psr\Http\Message\ServerRequestInterface;

if (!function_exists('session')) {
    /**
     * @param string|null $key
     * @param mixed|null $value
     * @return mixed|Session|null
     */
    function session(string $key = null, mixed $value = null): mixed
    {
        try {
            $session = app()->get(Session::class);
            if ($key) {
                if ($value) {
                    $session->set($key, $value);
                }
                return $session->get($key) ?? null;
            }
            return $session;
        } catch (Exception $ex) {
            return null;
        }
    }
}

if (!function_exists('request')) {
    function request(): ServerRequestInterface
    {
        return Request::fromGlobals();
    }
}

if (!function_exists('router')) {
    function router(string $name = null, array $params = []): string|Router
    {
        if ($name) {
            return \MkyCore\Facades\Router::getUrlFromName($name, $params);
        }
        try {
            return app()->get(Router::class);
        } catch (Exception $e) {
            return new Router(app());
        }
    }
}

if (!function_exists('view')) {
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

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::getBaseInstance();
    }
}

if (!function_exists('path')) {
    function path(string $path = ''): string
    {
        try {
            return app()->get('path:base') . DIRECTORY_SEPARATOR . trim($path, '\/');
        } catch (Exception $ex) {
            return '';
        }
    }
}

if (!function_exists('auth')) {
    /**
     * @return AuthManager|null
     */
    function auth(): ?AuthManager
    {
        try {
            return app()->get(AuthManager::class);
        } catch (Exception $ex) {
            return null;
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to = null, int $status = 302): RedirectResponse
    {
        $redirect = new RedirectResponse();
        if (!is_null($to)) {
            return $redirect->to($to, $status);
        }
        return $redirect;
    }
}

if (!function_exists('now')) {
    function now(): Carbon
    {
        return Carbon::now();
    }
}

if (!function_exists('config')) {
    /**
     * @param string $key
     * @param string|null $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        try {
            $config = app()->get(Config::class);
            return $config->get($key, $default);
        } catch (Exception $ex) {
            return $default;
        }
    }
}

if (!function_exists('env')) {
    /**
     * @param string $key
     * @param string|null $default
     * @return string|array|bool|null
     */
    function env(string $key, string $default = null): string|array|bool|null
    {
        return getenv($key) ?: $default;
    }
}

if (!function_exists('csrf')) {
    /**
     * @return string
     */
    function csrf(): string
    {
        try {
            $token = app()->get(CsrfMiddleware::class)->generateToken();
            $name = CsrfMiddleware::FORM_KEY;
            return "<input type='hidden' name='$name' value='$token' />";
        } catch (Exception $ex) {
            return '';
        }
    }
}

if (!function_exists('method')) {
    /**
     * @param string $method
     * @return string
     */
    function method(string $method): string
    {
        $method = strtoupper($method);
        $name = Request::METHOD_KEY_FORM;
        return "<input type='hidden' name='$name' value='$method' />";
    }
}

if (!function_exists('asset')) {
    /**
     * @param ?string $asset
     * @return string
     */
    function asset(string $asset = null): string
    {
        try {
            $base = app()->get(Request::class)->baseUri();
            if (!$asset) {
                return $base;
            }
            return $base . '/assets/' . trim($asset, '/');
        } catch (Exception $ex) {
            return '';
        }
    }
}

if (!function_exists('public_path')) {
    /**
     * @param ?string $path
     * @return string
     */
    function public_path(string $path = null): string
    {
        try {
            $base = app()->get('path:public');
            if (!$path) {
                return $base;
            }
            return $base . '/' . trim($path, '/');
        } catch (Exception $ex) {
            return '';
        }
    }
}

if (!function_exists('tmp_path')) {
    /**
     * @param ?string $path
     * @return string
     */
    function tmp_path(string $path = null): string
    {
        try {
            $base = app()->get('path:tmp');
            if (!$path) {
                return $base;
            }
            return $base . '/' . trim($path, '/');
        } catch (Exception $ex) {
            return '';
        }
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200, array $headers = []): ?JsonResponse
    {
        try {
            return app()->get(JsonResponse::class)->make($data, $status, $headers);
        } catch (Exception $ex) {
            return null;
        }
    }
}