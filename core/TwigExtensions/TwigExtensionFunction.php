<?php

namespace MkyCore\TwigExtensions;

use Twig\TwigFunction;

class TwigExtensionFunction extends \Twig\Extension\AbstractExtension
{

    public function getFunctions(): array
    {
        return [
            new TwigFunction('route', [$this, 'route']),
            new TwigFunction('csrf', [$this, 'csrf'], ['is_safe' => ['html']]),
            new TwigFunction('method', [$this, 'method'], ['is_safe' => ['html']]),
            new TwigFunction('asset', [$this, 'asset']),
            new TwigFunction('public_path', [$this, 'public_path']),
            new TwigFunction('dump', [$this, 'dump']),
            new TwigFunction('dd', [$this, 'dd']),
        ];
    }

    public function route(string $name = null, array $params = []): \MkyCore\Facades\Router|string
    {
        return route($name, $params);
    }

    /**
     * @return string
     */
    public function csrf(): string
    {
        return csrf();
    }

    /**
     * @param string $method
     * @return string
     */
    public function method(string $method): string
    {
        return method($method);
    }

    /**
     * @param string $asset
     * @return string
     */
    public function asset(string $asset): string
    {
        return asset($asset);
    }

    /**
     * @param string $path
     * @return string
     */
    public function public_path(string $path): string
    {
        return public_path($path);
    }

    /**
     * @param mixed $var
     * @param mixed ...$moreVars
     * @return void
     */
    public function dump(mixed $var, mixed ...$moreVars): void
    {
        dump($var, ...$moreVars);
    }

    /**
     * @param mixed $var
     * @param mixed ...$moreVars
     * @return void
     */
    public function dd(mixed $var, mixed ...$moreVars): void
    {
        dd($var, ...$moreVars);
    }

}