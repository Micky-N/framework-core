<?php

namespace MkyCore\Router;

use Closure;
use Exception;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Router\RouteNeedParamsException;
use MkyCore\Request;
use ReflectionException;

class Route
{

    private array $params = [];
    private array $optionalParams = [];

    public function __construct(
        private readonly string        $url,
        private readonly array         $methods,
        private readonly Closure|array $action = [],
        private readonly string        $module = '',
        private string                 $name = '',
        private array                  $middlewares = [],
        private array                  $permissions = []
    )
    {
    }

    public function compare(string $compareTo, array|string $value): bool
    {
        if (!method_exists($this, 'match' . ucfirst($compareTo))) {
            return false;
        }
        return $this->{'match' . ucfirst($compareTo)}($value);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $urlToCompare
     * @return bool
     */
    public function matchUrl(string $urlToCompare): bool
    {
        if (str_starts_with($urlToCompare, '/')) {
            return (bool)preg_match($urlToCompare, $this->url);
        }
        return $urlToCompare === $this->url;
    }

    /**
     * @param string $nameToCompare
     * @return bool
     */
    public function matchName(string $nameToCompare): bool
    {
        if (str_starts_with($nameToCompare, '/')) {
            return (bool)preg_match($nameToCompare, $this->name);
        }
        return $nameToCompare === $this->name;
    }

    /**
     * @return Closure|array
     */
    public function getAction(): Closure|array
    {
        return $this->action;
    }

    /**
     * @param string $controllerToCompare
     * @return bool
     */
    public function matchController(string $controllerToCompare): bool
    {
        if (str_starts_with($controllerToCompare, '/')) {
            return (bool)preg_match($controllerToCompare, $this->action[0]);
        }
        return $controllerToCompare === $this->action[0];
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param array|string $middlewaresToCompare
     * @return bool
     */
    public function matchMiddlewares(array|string $middlewaresToCompare): bool
    {
        foreach ((array)$middlewaresToCompare as $middleware) {
            if (!in_array($middleware, $this->middlewares)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function check(Request $request): bool
    {
        $path = trim($request->path(), '/');
        preg_match("#^{$this->urlRegex()}$#", $path, $m);
        return !empty($m) && str_starts_with($path, $m[0]);
    }

    private function urlRegex(): array|string|null
    {
        return preg_replace_callback('/\{(.*?)\}/', function ($e) {
            if (isset($e[1])) {
                return '([\w]*?)';
            }
            return $e[0];
        }, trim($this->url, '/'));
    }

    /**
     * @throws Exception
     */
    public function process(Request $request): ?Route
    {
        $urlParts = explode('/', trim($this->url, '/'));
        $pathParts = explode('/', trim($request->path(), '/'));
        $routeParams = [];
        array_map(function ($urlPart, $pathPart) use (&$routeParams) {
            return preg_replace_callback('/(.*?)\{(.*?)\}/', function ($e) use ($pathPart, &$routeParams) {
                if (isset($e[2])) {
                    $slug = $e[1];
                    $param = $e[2];
                    $value = $slug !== "" ? str_replace($slug, '', $pathPart) : $pathPart;
                    $isOptional = str_ends_with($param, '?');
                    if ($isOptional) {
                        $param = str_replace('?', '', $param);
                        $this->optionalParams[$param] = true;
                    }
                    $routeParams[$param] = $value;
                }
                return $e[0];
            }, $urlPart);
        }, $urlParts, $pathParts);
        $routeParams = array_filter($routeParams, fn($param) => $param);
        $this->params = $routeParams;
        return $this;
    }

    /**
     * @param array $routeParams
     * @return mixed
     * @throws ReflectionException
     * @throws RouteNeedParamsException
     */
    public function resolveByController(array $routeParams): mixed
    {
        $controllerReflection = new \ReflectionClass($this->action[0]);
        $methodReflection = $controllerReflection->getMethod($this->action[1]);
        $params = array_map(function ($reflectionParameter) use ($routeParams) {
            $name = $reflectionParameter->getName();
            if (array_key_exists($name, $routeParams)) {
                return $routeParams[$name];
            }
            if ($reflectionParameter->isDefaultValueAvailable()) {
                return $reflectionParameter->getDefaultValue();
            }
            throw new RouteNeedParamsException("Param \$$name required");
        }, $methodReflection->getParameters());
        return $methodReflection->invokeArgs($controllerReflection->newInstanceArgs(), $params);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param array $params
     * @param bool $absolute
     * @return string
     * @throws ReflectionException
     * @throws RouteNeedParamsException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function makeUrlFromName(array $params = [], bool $absolute = true): string
    {
        $request = app()->get(Request::class);
        $i = 0;
        return ($absolute ? $request->baseUri() . '/' : '/') . trim(preg_replace_callback('/\{(.*?)\}/', function ($query) use ($params, &$i) {
                $i++;
                array_shift($query);
                $query = $query[0] ?? false;
                if ($query) {
                    $isOptional = str_ends_with($query, '?');
                    $query = str_replace('?', '', $query);
                    if ($params && isset($params[$query])) {
                        return $params[$query];
                    } elseif ($isOptional) {
                        if (count($params) >= $i) {
                            if (!empty($params[$query])) {
                                return $params[$query];
                            }
                            $err = trim($query, '/');
                            throw new RouteNeedParamsException("Param \"$err\" required");
                        }
                        return '';
                    }
                    $err = trim($query, '/');
                    throw new RouteNeedParamsException("Param \"$err\" required");
                }
                return $query;
            }, $this->url), '/');
    }

    public function hasParam(): bool
    {
        return preg_match('/\{(.*)\}/', $this->url);
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getOptionalParams(): array
    {
        return $this->optionalParams;
    }

    public function isOptionalParam(string $param): bool
    {
        return isset($this->optionalParams[$param]) && $this->optionalParams[$param] === true;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param string $name
     * @return Route
     */
    public function as(string $name): Route
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param array $middlewares
     * @return Route
     */
    public function middlewares(array $middlewares): Route
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @param array $permissions
     * @return Route
     */
    public function allows(array $permissions): Route
    {
        $this->permissions = $permissions;
        return $this;
    }
}