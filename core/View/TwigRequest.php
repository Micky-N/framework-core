<?php

namespace MkyCore\View;


use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Facades\Config;
use MkyCore\Request;
use ReflectionException;

class TwigRequest
{
    public function __construct(private readonly Request $request)
    {
    }

    public function session(string $name = null, mixed $default = null)
    {
        return $this->request->session($name, $default);
    }

    public function old(string $name, mixed $default = null)
    {
        return $this->request->old($name, $default);
    }

    public function hasOld(string $name)
    {
        return $this->request->hasOld($name);
    }

    public function flash(string $name, mixed $default = null)
    {
        return $this->request->flash($name, $default);
    }

    public function hasFlash(string $name)
    {
        return $this->request->hasFlash($name);
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     * @throws ReflectionException
     */
    public function auth()
    {
        return $this->request->auth();
    }

    public function config(string $key, mixed $default = null)
    {
        return Config::get($key, $default);
    }
}