<?php

namespace MkyCore\Router;

use MkyCore\Str;

class RouteCrud
{
    public function __construct(private readonly string $namespace, private readonly array $routes)
    {
    }

    /**
     * Set route name by method
     *
     * @param array $methods
     * @return $this
     * @example 'index' => 'foo' means route name with index method will be 'foo.index'
     * '*' => 'foo' means all routes names will start with 'foo'
     */
    public function as(array $methods): RouteCrud
    {
        foreach ($methods as $method => $name) {
            if (!isset($this->routes[$method])) {
                continue;
            }
            if (str_starts_with($name, '*')) {
                $name = str_replace('*', '', $name);
                $this->routes[$method]->as($this->routes[$method]->getName() . $name);
            } elseif (str_ends_with($name, '*')) {
                $name = str_replace('*', '', $name);
                $this->routes[$method]->as($name . $this->routes[$method]->getName());
            } else {
                $this->routes[$method]->as($name);
            }
        }
        return $this;
    }

    /**
     * Keep routes matching methods
     *
     * @param array $methods
     * @return $this
     */
    public function only(array $methods): static
    {
        $routes = [];
        foreach ($this->routes as $mt => $route) {
            if (!in_array($mt, $methods)) {
                $routes[] = $this->routes[$mt];
            }
        }
        for ($i = 0; $i < count($routes); $i++) {
            $route = $routes[$i];
            \MkyCore\Facades\Router::deleteRoute($route);
        }
        return $this;
    }

    /**
     * Remove routes matching methods
     *
     * @param array $methods
     * @return $this
     */
    public function except(array $methods): static
    {
        $routes = [];
        foreach ($this->routes as $mt => $route) {
            if (in_array($mt, $methods)) {
                $routes[] = $this->routes[$mt];
            }
        }
        for ($i = 0; $i < count($routes); $i++) {
            $route = $routes[$i];
            \MkyCore\Facades\Router::deleteRoute($route);
        }
        return $this;
    }

    /**
     * Set route middlewares by method
     *
     * @param array $methods
     * @return $this
     * @example 'index' => ['foo'] means route with index method will have a foo middleware
     * '*' => ['foo'] means all routes will have a foo middleware
     */
    public function middlewares(array $methods): RouteCrud
    {
        if (isset($methods['*'])) {
            $middlewares = $methods['*'];
            foreach ($this->routes as $method => $route) {
                $this->routes[$method]->middlewares($middlewares);
            }
            unset($methods['*']);
        }
        if ($methods) {
            foreach ($methods as $method => $middlewares) {
                $oldMiddlewares = $this->routes[$method]->getMiddlewares();
                $this->routes[$method]->middlewares([...$oldMiddlewares, ...$middlewares]);
            }
        }
        return $this;
    }

    /**
     * Set route permission by method
     *
     * @param array $methods
     * @return $this
     * @example 'index' => ['foo'] means route with index method will have a foo permission
     * '*' => ['foo'] means all routes will have a foo permission
     */
    public function allows(array $methods): RouteCrud
    {
        if (isset($methods['*'])) {
            $allows = $methods['*'];
            foreach ($this->routes as $method => $route) {
                $this->routes[$method]->allows($allows);
            }
            unset($methods['*']);
        }
        if ($methods) {
            foreach ($methods as $method => $allows) {
                $oldMiddlewares = $this->routes[$method]->getPermissions();
                $this->routes[$method]->allows([...$oldMiddlewares, ...$allows]);
            }
        }

        return $this;
    }

    /**
     * Set route param name by method
     *
     * @param array $params
     * @return $this
     * @example url => 'users/{user}', method => 'index'
     * 'index' => 'foo' means url of route with index method will be 'users/{foo}'
     * '*' => 'foo' means all routes
     *
     * @example url => 'users/{user}/blogs/{blog} method => 'index'
     * 'index' => 'foo.bar' means url of route with index method will be 'users/{foo}/blogs/{bar}'
     */
    public function params(array $params): RouteCrud
    {
        $newNamespaces = [];
        if (isset($params['*'])) {
            $param = $params['*'];
            foreach ($this->routes as $method => $route) {
                $oldUrl = $this->routes[$method]->getUrl();
                $namespaces = explode('.', $this->namespace);
                $paramExplode = explode('.', $param);
                for ($i = 0; $i < count($namespaces); $i++) {
                    $namespace = $namespaces[$i];
                    $currentParam = $paramExplode[$i] ?? Str::singularize($namespace);
                    $oldUrl = str_replace('{' . Str::singularize($namespace) . '}', '{' . $currentParam . '}', $oldUrl);
                    $newNamespaces[] = $currentParam;
                }
                $this->routes[$method]->setUrl($oldUrl);
            }
            unset($params['*']);
        }
        $namespaces = $newNamespaces ?? null;
        if ($params) {
            foreach ($params as $method => $param) {
                $oldUrl = $this->routes[$method]->getUrl();
                if (!$namespaces) {
                    $namespaces = explode('.', $this->namespace);
                }
                $paramExplode = explode('.', $param);
                for ($i = 0; $i < count($namespaces); $i++) {
                    $namespace = $namespaces[$i];
                    $currentParam = $paramExplode[$i] ?? Str::singularize($namespace);
                    $oldUrl = str_replace('{' . Str::singularize($namespace) . '}', '{' . $currentParam . '}', $oldUrl);
                }
                $this->routes[$method]->setUrl($oldUrl);
            }
        }
        return $this;
    }
}