<?php

namespace MkyCore\TwigExtensions;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;
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
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function csrf(): string
    {
        return csrf();
    }

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    public function method(string $method): string
    {
        return method($method);
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function asset(string $asset): string
    {
        return asset($asset);
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     * @throws ReflectionException
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
    public function dump(mixed $var, mixed ...$moreVars)
    {
        dump($var, ...$moreVars);
    }

    /**
     * @param mixed $var
     * @param mixed ...$moreVars
     * @return void
     */
    public function dd(mixed $var, mixed ...$moreVars)
    {
        dd($var, ...$moreVars);
    }

}