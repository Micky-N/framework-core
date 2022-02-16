<?php

namespace MkyCore;

use MkyCore\Exceptions\Router\RouteAlreadyExistException;
use MkyCore\Exceptions\Router\RouteNeedParamsException;
use MkyCore\Exceptions\Router\RouteNotFoundException;
use MkyCore\Facades\Session;
use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];

    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    /**
     * Set GET method route
     *
     * @param string $path
     * @param callable|array $action
     * @param string|null $name
     * @param array|string|null $middleware
     * @param string|null $module
     * @return Route
     * @throws RouteAlreadyExistException
     */
    public function get(string $path, $action, string $name = '', $middleware = null, string $module = null): Route
    {
        if($this->checkIfAlreadyRouteExist('GET', $path)){
            throw new RouteAlreadyExistException("Route $path already exist in GET routes");
        }
        $route = new Route($path, $action, $name, $middleware, $module);
        $this->routes['GET'][] = $route;
        return $route;
    }

    /**
     * Set POST method route
     *
     * @param string $path
     * @param callable|array $action
     * @param string|null $name
     * @param array|string|null $middleware
     * @param string|null $module
     * @return Route
     * @throws RouteAlreadyExistException
     */
    public function post(string $path, $action, string $name = '', $middleware = null, string $module = null): Route
    {
        if($this->checkIfAlreadyRouteExist('POST', $path)){
            throw new RouteAlreadyExistException("Route $path already exist in POST routes");
        }
        $route = new Route($path, $action, $name, $middleware, $module);
        $this->routes['POST'][] = $route;
        return $route;
    }

    /**
     * Check if route already exist in request method
     *
     * @param string $requestMethod
     * @param string $path
     * @return bool
     */
    private function checkIfAlreadyRouteExist(string $requestMethod, string $path)
    {
        if(isset($this->routes[$requestMethod])){
            foreach ($this->routes[$requestMethod] as $route) {
                if(trim($path, '/') === trim($route->getPath(), '/')){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all routes by name
     *
     * @return Route[]
     */
    public function routesByName(): array
    {
        $routesNamed = [];
        foreach ($this->routes as $request => $routesRequest) {
            foreach ($this->routes[$request] as $route) {
                $routesNamed[$route->name] = $route;
            }
        }
        return $routesNamed;
    }

    /**
     * Create all crud model routes
     *
     * @param string $namespace
     * @param string $controller
     * @param array $middleware
     * @param array $only
     * @param string|null $moduleName
     * @param bool $isAdminRoute
     * @return Router
     */
    public function crud(string $namespace, string $controller, array $middleware = [], array $only = [], string $moduleName = null, bool $isAdminRoute = false): Router
    {
        $path = '';
        $action = '';
        if(strpos($namespace, '.')){
            $namespaces = explode('.', $namespace);
            $namespace = end($namespaces);
            foreach ($namespaces as $key => $name) {
                if($key < count($namespaces) - 1){
                    $path .= "$name/:" . get_singular($name) . '/';
                }
            }
            array_shift($namespaces);
            $action = join('', array_map(function ($n) {
                return ucfirst(get_singular($n));
            }, $namespaces));
        }
        $id = "/:" . strtolower(get_singular($namespace));

        $crudActions = [
            'index' => [
                'request' => 'get',
                'path' => "{$path}$namespace",
                'action' => [$controller, 'index' . $action],
                'name' => "$namespace.index",
            ],
            'show' => [
                'request' => 'get',
                'path' => "{$path}$namespace{$id}",
                'action' => [$controller, 'show' . $action],
                'name' => "$namespace.show",
            ],
            'new' => [
                'request' => 'get',
                'path' => "{$path}$namespace/new",
                'action' => [$controller, 'new' . $action],
                'name' => "$namespace.new",
            ],
            'create' => [
                'request' => 'post',
                'path' => "{$path}$namespace",
                'action' => [$controller, 'create' . $action],
                'name' => "$namespace.create",
            ],
            'edit' => [
                'request' => 'get',
                'path' => "{$path}$namespace/edit{$id}",
                'action' => [$controller, 'edit' . $action],
                'name' => "$namespace.edit",
            ],
            'update' => [
                'request' => 'post',
                'path' => "{$path}$namespace/update{$id}",
                'action' => [$controller, 'update' . $action],
                'name' => "$namespace.update",
            ],
            'delete' => [
                'request' => 'get',
                'path' => "{$path}$namespace/delete{$id}",
                'action' => [$controller, 'delete' . $action],
                'name' => "$namespace.delete",
            ],
        ];
        foreach ($crudActions as $key => $crudAction) {
            if(!empty($only) && in_array($key, $only)){
                call_user_func_array(
                    [$this, $crudAction['request']],
                    [($isAdminRoute ? 'admin/' : '') . $crudAction['path'], $crudAction['action'], ($isAdminRoute ? 'admin.' : '') . $crudAction['name'], $middleware, $moduleName]
                );
            } elseif(empty($only)) {
                call_user_func_array(
                    [$this, $crudAction['request']],
                    [($isAdminRoute ? 'admin/' : '') . $crudAction['path'], $crudAction['action'], ($isAdminRoute ? 'admin.' : '') . $crudAction['name'], $middleware, $moduleName]
                );
            }
        }
        return $this;
    }

    /**
     * Check if route needs params
     *
     * @param string $path
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function routeNeedParams(string $path, array $params = []): string
    {
        $path = explode('/', trim($path, '/'));
        foreach ($path as $key => $value) {
            if(strpos($value, ':') !== false){
                preg_match('/(.*)?:(.*)?/', $value, $matches);
                if(!empty($params) && isset($params[$matches[2]])){
                    $path[$key] = ($matches[1] ?? '') . $params[$matches[2]];
                } else {
                    throw new RouteNeedParamsException("No value for the parameter $matches[2]");
                }
            }
        }
        $path = "/" . implode('/', $path);
        return $path;
    }

    /**
     * Generate url by route name
     *
     * @param string $routeName
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function generateUrlByName(string $routeName, array $params = []): string
    {
        $requestTest = '';
        foreach ($this->routes as $request => $routesByRequest) {
            $requestTest = $request;
            foreach ($routesByRequest as $route) {
                if($route->name === $routeName){
                    return $this->routeNeedParams($route->path, $params);
                }
            }
        }
        throw new RouteNotFoundException("Route '$routeName' not found", 404);
    }

    /**
     * Get the current route
     * or if route is the currently used
     *
     * @param string $route
     * @param bool $path
     * @return bool|string
     */
    public function currentRoute(string $route = '', bool $path = false)
    {
        $currentPath = ServerRequest::fromGlobals()->getUri()->getPath();
        if($route && $path === false){
            $currentRoute = array_filter($this->routesByName(), fn($route) => trim($route->getPath(), '/') === trim($currentPath, '/'));
            return $path ? $currentRoute[key($currentRoute)]->getPath() === $route : key($currentRoute) === $route;
        }
        return trim($currentPath, '/') === trim($route, '/');
    }

    public function isModuleRoute()
    {
        $currentPath = ServerRequest::fromGlobals()->getUri()->getPath();
        $currentRoute = array_filter($this->routesByName(), fn($route) => trim($route->getPath(), '/') === trim($currentPath, '/'));
        $currentRoute = $currentRoute[key($currentRoute)];
        return !is_null($currentRoute->getModule());
    }

    /**
     * Run router application
     *
     * @param ServerRequestInterface $request
     * @return void|View
     * @throws Exception
     */
    public function run(ServerRequestInterface $request)
    {
        if(isset($this->routes[$request->getMethod()])){
            foreach ($this->routes[$request->getMethod()] as $route) {
                if($route->match($request)){
                    return $route->execute($request);
                }
            }
        }
        throw new RouteNotFoundException(sprintf('Route %s not found', $request->getUri()->getPath()), 404);
    }

    /**
     * Redirect to url
     *
     * @param string $url
     */
    public function redirect(string $url): void
    {
        header("Location: $url");
    }

    /**
     * Redirect to route name url
     *
     * @param string $name
     * @return Router
     * @throws Exception
     */
    public function redirectName(string $name): self
    {
        $path = $this->generateUrlByName($name);
        header("Location: $path");
        return $this;
    }

    /**
     * Redirect with errors flash message
     *
     * @param array $errors
     * @return $this
     */
    public function withError(array $errors): self
    {
        foreach ($errors as $name => $message) {
            Session::setFlashMessageOnType(Session::getConstant('FLASH_ERROR'), $name, $message);
        }
        return $this;
    }

    /**
     * Redirect with success flash message
     *
     * @param array $success
     * @return $this
     */
    public function withSuccess(array $success): self
    {
        foreach ($success as $name => $message) {
            Session::setFlashMessageOnType(Session::getConstant('FLASH_SUCCESS'), $name, $message);
        }
        return $this;
    }

    /**
     * Redirect with flash message
     *
     * @param array $messages
     * @return $this
     */
    public function with(array $messages): self
    {
        foreach ($messages as $name => $message) {
            Session::setFlashMessageOnType(Session::getConstant('FLASH_MESSAGE'), $name, $message);
        }
        return $this;
    }

    /**
     * Back redirection
     *
     * @return Router
     */
    public function back(): self
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        return $this;
    }

    /**
     * Affiche les routes sont forme de tableau
     * pour cli
     *
     * @return array
     */
    public function toArray(): array
    {
        App::RoutesInit();
        $routesArray = [];
        $namespace = '/';
        $currentPath = "\t";
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                $paths = explode('/', trim($route->path, '/'));
                if(in_array($namespace, $paths)){
                    unset($paths[0]);
                    $currentPath = "\t/" . join('/', $paths);
                } else {
                    $namespace = $paths[0];
                    $currentPath = $route->path;
                }
                $routesArray[] = [
                    $method,
                    $currentPath,
                    is_array($route->action) ? str_replace(
                        'ProductModule\\Http\\Controllers\\',
                        '',
                        $route->action[0]
                    ) : getTypeName($route->action),
                    is_array($route->action) ? $route->action[1] : null,
                    $route->name,
                    $route->middleware,
                    config('structure') === 'HMVC' ? ($route->module ?? null) : null
                ];
            }
        }
        return $routesArray;
    }

    /**
     * @param array $routesYaml
     * @param Module $module
     * @param bool $isAdminRoute
     */
    public function parseRoutes(array $routesYaml, Module $module = null, bool $isAdminRoute = false)
    {
        $configModule = $module ? include $module::CONFIG : null;
        $moduleName = !is_null($module) ? get_class($module) : null;
        foreach ($routesYaml as $namespace => $routesName) {
            foreach ($this->arrayNamespaces($routesName, [($isAdminRoute ? 'admin.' : '') . $namespace]) as $name => $route) {
                $middleware = isset($route['middleware']) ? (array) $route['middleware'] : [];
                if($route['path'] === 'crud'){
                    $name = str_replace('admin.', '', $name);
                    $only = $route['only'] ?? [];
                    $this->crud($name, $route['controller'], $middleware, $only, $moduleName, $isAdminRoute);
                } else {
                    $moduleRoot = !is_null($module) ? $module->getRoot() : null;
                    $action = $this->getAction($route['action'], $moduleRoot);
                    $path = ($isAdminRoute ? '/admin' : '') . '/' . trim(($configModule['url_prefix'] ?? ''), '/') . '/' . trim($route['path'], '/');
                    $this->{$route['method']}($path, $action, $name, $middleware, $moduleName);
                }
            }
        }
    }

    private function arrayNamespaces($array, $name = [])
    {
        $newArray = [];
        if(!key_exists('path', $array)){
            foreach ($array as $k => $arr) {
                if(!key_exists('path', $arr)){
                    $name[] = $k;
                    $newArray[join('.', [...$name])] = $arr;
                    $array = $this->arrayNamespaces($arr, $name);
                    $newArray = array_filter(array_merge($newArray, $array), function ($a) {
                        return key_exists('path', $a);
                    });
                } else {
                    $newArray[join('.', [...$name, $k])] = $arr;
                }
            }
            return $newArray;
        } else {
            $newArray[join('.', [...$name])] = $array;
            return $newArray;
        }
    }

    private function getAction(string $action, string $moduleRoot = null)
    {
        $action = explode('::', $action);
        if($action[0] === 'func'){
            $functions = include ($moduleRoot ?? ROOT) . '/routes/functions.php';
            return $functions[$action[1]];
        }
        return array_filter([$action[0], $action[1] ?? null]);
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
