<?php

namespace MkyCore\Router;

class RouteCrud
{
    public function __construct(private readonly string $namespace, private readonly array $routes, private array $params)
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
     * Set route param name for all method
     *
     * @param string $namespace
     * @param string $param
     * @return $this
     */
    public function paramFor(string $namespace, string $param): RouteCrud
    {
        foreach ($this->routes as $method => $route) {
            $oldUrl = $this->routes[$method]->getUrl();
            if (!($oldParam = $this->params[$namespace])) {
                continue;
            }
            $newUrl = str_replace('{' . $oldParam . '}', '{' . $param . '}', $oldUrl);
            $this->routes[$method]->setUrl($newUrl);
            $this->params[$namespace] = $param;
        }
        return $this;
    }

    /**
     * Keep only api controller method
     *
     * @return $this
     */
    public function apiOnly(): RouteCrud
    {
        return $this->only(['index', 'store', 'show', 'update', 'destroy']);
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
}