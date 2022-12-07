<?php

namespace MkyCore\TwigExtensions;

use Twig\TwigFunction;

class TwigExtensionFunction extends \Twig\Extension\AbstractExtension
{

    public function getFunctions()
    {
        return [
            new TwigFunction('route', [$this, 'route']),
            new TwigFunction('session', [$this, 'session']),
            new TwigFunction('auth', [$this, 'auth']),
            new TwigFunction('csrf', [$this, 'csrf'], ['is_safe' => ['html']]),
            new TwigFunction('method', [$this, 'method'], ['is_safe' => ['html']]),
            new TwigFunction('asset', [$this, 'asset']),
            new TwigFunction('public_path', [$this, 'public_path']),
        ];
    }

    public function route(string $name = null, array $params = [])
    {
        return route($name, $params);
    }

    public function session(string $key = null, mixed $value = null)
    {
        return session($key, $value);
    }

    public function auth()
    {
        return auth();
    }

    public function csrf()
    {
        return csrf();
    }

    public function method(string $method)
    {
        return method($method);
    }

    public function asset(string $asset)
    {
        return asset($asset);
    }

    public function public_path(string $path)
    {
        return public_path($path);
    }

}