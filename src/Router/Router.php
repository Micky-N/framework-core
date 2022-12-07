<?php

namespace MkyCore\Router;

use Closure;
use Exception;
use GuzzleHttp\Psr7\Request;
use ReflectionException;
use MkyCore\Annotation\Annotation;
use MkyCore\Application;
use MkyCore\Exceptions\Router\RouteAlreadyExistException;
use MkyCore\Exceptions\Router\RouteNeedParamsException;
use MkyCore\Exceptions\Router\RouteNotFoundException;

class Router
{
    const DEFAULT_PARAMS = [
        'methods' => ['GET']
    ];

    const METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * @var Route[][]
     */
    private array $routes = [];

    private Route $currentRoute;

    public function __construct(private readonly Application $app)
    {
        $methods = self::METHODS;
        for ($i = 0; $i < count($methods); $i++){
            $method = $methods[$i];
            $this->routes[$method] = [];
        }
    }

    /**
     * @param string $controllerRootPath
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function getRoutesFromAnnotation(string $controllerRootPath): void
    {
        foreach ($this->getAllControllerDirs($controllerRootPath) as $controllerDir) {
            $controller = ucfirst(trim(str_replace([$this->app->get('path:base'), '.php'], ['', ''], $controllerDir), DIRECTORY_SEPARATOR));
            preg_match('/\\\(.*Module)/', $controller, $matches);
            $module = $matches[1] ?? 'root';
            $controller = new Annotation($controller);
            $controllerAnnotations = $controller->getClassAnnotation('Router');
            $methodsAnnotations = $controller->getMethodsAnnotations();
            if ($methodsAnnotations) {
                foreach ($methodsAnnotations as $nameMethod => $methodAnnotation) {
                    $methodRouter = $methodAnnotation->getParam('Router');
                    $routeArray = [
                        'url' => trim($controllerAnnotations ? $controllerAnnotations->default : '', '/') . '/' . trim($methodRouter->default, '/'),
                        'methods' => array_map(fn($method) => strtoupper($method), $methodRouter->methods ?? ['GET']),
                        'action' => [$controller->getName(), $nameMethod],
                        'name' => ($controllerAnnotations && $controllerAnnotations->as) && $methodRouter->as ? $controllerAnnotations->as . '.' . $methodRouter->as : $methodRouter->as,
                        'middlewares' => array_merge($controllerAnnotations->middlewares ?? [], $methodRouter->middlewares ?? []),
                        'module' => $module,
                        'permissions' => $methodRouter->cans ?? []
                    ];
                    $this->addRoute($routeArray);
                }
            }
        }
    }

    private function getAllControllerDirs(string $controllerRootPath): array
    {
        $controllers = [];
        return $this->getAllControllerFiles($controllerRootPath, $controllers);
    }

    private function getAllControllerFiles($controllerPath, array &$arrayRes = []): array
    {
        foreach (scandir($controllerPath) as $path) {
            if (in_array($path, ['.', '..'])) {
                continue;
            }
            $fullPath = $controllerPath . DIRECTORY_SEPARATOR . $path;
            if (is_dir($fullPath)) {
                $this->getAllControllerFiles($fullPath, $arrayRes);
            } else if (preg_match('/.*Controller.php$/', $fullPath)) {
                $arrayRes[] = $fullPath;
            }
        }
        return $arrayRes;
    }

    /**
     * Set GET method route
     *
     * @param string $url
     * @param Closure|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function get(string $url, Closure|array $action, string $module = 'root'): Route
    {
        return $this->addRoute([
            'methods' => 'GET',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * @param array $routeData
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function addRoute(array $routeData): Route
    {
        $routeData['methods'] = (array)($routeData['methods'] ?: self::DEFAULT_PARAMS['methods']);
        $route = new Route($routeData['url'], $routeData['methods'], $routeData['action'] ?? [], ucfirst($routeData['module'] ?? 'root'), $routeData['name'] ?? '', $routeData['middlewares'] ?? [], $routeData['permissions'] ?? []);
        foreach ($routeData['methods'] as $method) {
            $method = strtoupper($method);
            if (in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
                if ($this->checkIfAlreadyRouteExist($method, $route->getUrl())) {
                    throw new RouteAlreadyExistException("This route \"{$route->getUrl()}\" is already exists");
                }
                $this->routes[$method][] = $route;
            } else {
                throw new RouteNotFoundException("Request method '$method' doesn't exist or it's not handle");
            }
        }
        return $route;
    }

    /**
     * Check if route already exist in request method
     *
     * @param string $requestMethod
     * @param string $path
     * @return bool
     */
    private function checkIfAlreadyRouteExist(string $requestMethod, string $path): bool
    {
        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $route) {
                if (trim($path, '/') === trim($route->getUrl(), '/')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set GET method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function put(string $url, callable|array $action, string $module = 'root'): Route
    {
        return $this->addRoute([
            'methods' => 'PUT',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * Set GET method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function delete(string $url, callable|array $action, string $module = 'root'): Route
    {
        return $this->addRoute([
            'methods' => 'DELETE',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * Set POST method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function post(string $url, callable|array $action, string $module = 'root'): Route
    {
        return $this->addRoute([
            'methods' => 'POST',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * Create all crud model routes
     *
     * @param string $namespace
     * @param string $controller
     * @param string $moduleName
     * @param array $only
     * @return RouteCrud
     */
    public function crud(string $namespace, string $controller, string $moduleName = 'root'): RouteCrud
    {
        $path = '';
        $action = '';
        $moduleName = $moduleName == 'root' ? $moduleName : ucfirst($moduleName).'Module';
        if (strpos($namespace, '.')) {
            $namespaces = explode('.', $namespace);
            $namespace = end($namespaces);
            foreach ($namespaces as $key => $name) {
                if ($key < count($namespaces) - 1) {
                    $path .= "$name/\{$name}/";
                }
            }
            array_shift($namespaces);
            $action = join('', $namespaces);
        }
        $id = "/{" . strtolower($namespace) . "}";

        $crudActions = [
            'index' => [
                'request' => 'get',
                'url' => "{$path}$namespace",
                'action' => [$controller, 'index' . $action],
                'name' => "$namespace.index",
                'module' => $moduleName
            ],
            'show' => [
                'request' => 'get',
                'url' => "{$path}$namespace{$id}",
                'action' => [$controller, 'show' . $action],
                'name' => "$namespace.show",
                'module' => $moduleName
            ],
            'create' => [
                'request' => 'get',
                'url' => "{$path}$namespace/new",
                'action' => [$controller, 'new' . $action],
                'name' => "$namespace.new",
                'module' => $moduleName
            ],
            'store' => [
                'request' => 'post',
                'url' => "{$path}$namespace",
                'action' => [$controller, 'create' . $action],
                'name' => "$namespace.create",
                'module' => $moduleName
            ],
            'edit' => [
                'request' => 'get',
                'url' => "{$path}$namespace/edit{$id}",
                'action' => [$controller, 'edit' . $action],
                'name' => "$namespace.edit",
                'module' => $moduleName
            ],
            'update' => [
                'request' => 'put',
                'url' => "{$path}$namespace{$id}/update",
                'action' => [$controller, 'update' . $action],
                'name' => "$namespace.update",
                'module' => $moduleName
            ],
            'delete' => [
                'request' => 'delete',
                'url' => "{$path}$namespace{$id}/delete",
                'action' => [$controller, 'delete' . $action],
                'name' => "$namespace.delete",
                'module' => $moduleName
            ],
        ];
        $routeCrud = [];
        foreach ($crudActions as $key => $crudAction) {
            $crudKey = str_replace($action, '', $crudAction['action'][1]);
            $routeCrud[$crudKey] = call_user_func_array(
                [$this, $crudAction['request']],
                [$crudAction['url'], $crudAction['action'], $moduleName]
            )->as($crudAction['name']);
        }
        return new RouteCrud($namespace, $routeCrud);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function match(Request $request): ?Route
    {
        $routes = $this->routes[$request->getMethod()];
        $this->sortRoutes($routes);
        for ($i = 0; $i < count($routes); $i++) {
            $route = $routes[$i];
            if ($route->check($request)) {
                $this->currentRoute = $route;
                return $route->process($request);
            }
        }
        return null;
    }

    private function sortRoutes(array &$routes): void
    {
        usort($routes, function ($routeA, $routeB) {
            $arrayA = explode('/', $routeA->getUrl());
            $arrayB = explode('/', $routeB->getUrl());
            $BGreater = count($arrayB) > count($arrayA);
            return $BGreater || $routeA->hasParam() ? 1 : -1;
        });
    }

    public function deleteRoute(Route $route): void
    {
        foreach ($route->getMethods() as $method){
            $method = strtoupper($method);
            $this->routes[$method] = array_filter($this->routes[$method], fn(Route $r) => $r !== $route);
        }
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $absolute
     * @return string
     * @throws RouteNotFoundException
     * @throws RouteNeedParamsException
     */
    public function getUrlFromName(string $name, array $params = [], bool $absolute = true): string
    {
        foreach ($this->getRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route->makeUrlFromName($params, $absolute);
            }
        }
        throw new RouteNotFoundException("Route with name '$name' not found");
    }

    /**
     * @param array $filters
     * @return Route[]
     */
    public function getRoutes(array $filters = []): array
    {
        $routes = [];
        $methods = self::METHODS;
        if (isset($filters['methods'])) {
            $methodsFilter = array_map(fn($m) => strtoupper($m), (array)$filters['methods']);
            $methods = array_filter($methods, function ($method) use ($methodsFilter) {
                return in_array(strtoupper($method), $methodsFilter);
            });
            unset($filters['methods']);
        }
        foreach ($methods as $method) {
            if (isset($this->routes[$method])) {
                $routes = array_merge($routes, $this->routes[$method]);
            }
        }
        if ($filters) {
            $routes = [...array_filter($routes, function (Route $route) use ($filters) {
                foreach ($filters as $filter => $value) {
                    return $route->compare($filter, $value);
                }
                return false;
            })];
        }
        return $routes;
    }

    /**
     * @return Route
     */
    public function getCurrentRoute(): Route
    {
        return $this->currentRoute;
    }

    /**
     * Affiche les routes sont forme de tableau
     * pour cli
     *
     * @return array
     * @throws Exception
     */
    public function toArray(): array
    {
        $routesArray = [];
        $namespace = '/';
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                $paths = explode('/', trim($route->getUrl(), '/'));
                if(in_array($namespace, $paths)){
                    unset($paths[0]);
                    $currentPath = "\t/" . join('/', $paths);
                } else {
                    $namespace = $paths[0];
                    $currentPath = $route->getUrl();
                }
                $routesArray[] = [
                    $method,
                    $currentPath,
                    is_array($route->getAction()) ? str_replace(
                        'ProductModule\\Http\\Controllers\\',
                        '',
                        $route->getAction()[0]
                    ) : $this->getTypeName($route->getAction()),
                    is_array($route->getAction()) ? $route->getAction()[1] : null,
                    $route->getName(),
                    $route->getMiddlewares(),
                    $route->getModule()
                ];
            }
        }
        return $routesArray;
    }

    private function getTypeName($var): string
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }
}