<?php

namespace MkyCore\Router;

use Closure;
use Exception;
use GuzzleHttp\Psr7\Request;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Annotation\Annotation;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Router\RouteAlreadyExistException;
use MkyCore\Exceptions\Router\RouteNeedParamsException;
use MkyCore\Exceptions\Router\RouteNotFoundException;
use MkyCore\Facades\Config;
use MkyCore\File;
use ReflectionException;

class Router
{
    const DEFAULT_PARAMS = [
        'methods' => ['GET']
    ];

    const METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];

    /**
     * @var Route[][]
     */
    private array $routes = [];

    private Route $currentRoute;

    public function __construct(private readonly Application $app)
    {
        $methods = self::METHODS;
        for ($i = 0; $i < count($methods); $i++) {
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
            $module = $this->parseModule($controller);
            $alias = $module->getAlias();
            if (!in_array(Config::get($alias . '::app.route_mode', 'file'), ['controller', 'both'])) {
                continue;
            }
            $controller = new Annotation($controller);
            $controllerAnnotations = $controller->getClassAnnotation('Router');
            $methodsAnnotations = $controller->getMethodsAnnotations();
            if ($methodsAnnotations) {
                foreach ($methodsAnnotations as $nameMethod => $methodAnnotation) {
                    $methodRouter = $methodAnnotation->getParam('Router');
                    $controllerUrl = $controllerAnnotations && $controllerAnnotations->default ? trim($controllerAnnotations->default, '/') : '';
                    $controllerName = $controllerAnnotations && $controllerAnnotations->as && $methodRouter->as ? $controllerAnnotations->as . '.' : '';
                    $routeArray = [
                        'url' => $controllerUrl . '/' . trim($methodRouter->default, '/'),
                        'methods' => array_map(fn($method) => strtoupper($method), $methodRouter->methods ?? ['GET']),
                        'action' => [$controller->getName(), $nameMethod],
                        'name' => $controllerName . $methodRouter->as,
                        'middlewares' => array_merge($controllerAnnotations->middlewares ?? [], $methodRouter->middlewares ?? []),
                        'module' => $module->getAlias(),
                        'permissions' => array_merge($controllerAnnotations->allows ?? [], $methodRouter->allows ?? [])
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
    public function get(string $url, Closure|array $action, string $module = ''): Route
    {
        return $this->addRoute([
            'methods' => ['GET', 'HEAD'],
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
        if (empty($routeData['module'])) {
            $backtraces = debug_backtrace();
            $traces = array_filter($backtraces, fn($trace) => isset($trace['file']) && str_ends_with($trace['file'], 'start' . DIRECTORY_SEPARATOR . 'routes.php'));
            $traceFile = array_shift($traces)['file'];
            $moduleCheck = str_replace('start' . DIRECTORY_SEPARATOR . 'routes', '*Kernel', $traceFile);
            $checkKernelFiles = glob($moduleCheck);
            $checkKernelFile = array_shift($checkKernelFiles);
            $moduleKernelCheck = File::makeNamespace($checkKernelFile, true);
            if (!$moduleKernelCheck) {
                throw new RouteNotFoundException('Module not found');
            }
            $moduleKernel = app()->get($moduleKernelCheck);
            $routeData['module'] = $moduleKernel->getAlias();
        } else {
            $moduleKernel = $this->app->getModuleKernel($routeData['module']);
        }
        $prefix = trim($moduleKernel->getConfig('prefix', '/'), '/');
        $url = trim($routeData['url'], '/');
        $url = $prefix ? "$prefix/$url" : $url;
        $route = new Route($url, $routeData['methods'], $routeData['action'] ?? [], $routeData['module'], $routeData['name'] ?? '', $routeData['middlewares'] ?? [], $routeData['permissions'] ?? []);
        foreach ($routeData['methods'] as $method) {
            $method = strtoupper($method);
            if (in_array($method, self::METHODS)) {
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
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    private function parseModule(string $controller): ?ModuleKernel
    {
        $controller = new \ReflectionClass($controller);
        $controllerPath = $controller->getFileName();
        $explode = explode(DIRECTORY_SEPARATOR, $controllerPath);
        $explode = array_reverse($explode);
        $exp = '';
        for ($i = 0; $i < count($explode) - 1; $i++) {
            $exp = $explode[$i];
            if (!str_ends_with($exp, 'Module')) {
                unset($explode[$i]);
            } else {
                break;
            }
        }
        $explode = join(DIRECTORY_SEPARATOR, array_reverse($explode));
        $nameKernel = '';
        foreach (scandir($explode) as $path) {
            if (str_ends_with($path, 'Kernel.php')) {
                $nameKernel = str_replace('.php', '', $path);
                break;
            }
        }

        $namespace = $controller->getNamespaceName();
        $explode = explode(DIRECTORY_SEPARATOR, $namespace);
        $explode = array_reverse($explode);
        $exp = '';
        for ($i = 0; $i < count($explode) - 1; $i++) {
            $exp = $explode[$i];
            if (!str_ends_with($exp, 'Module')) {
                unset($explode[$i]);
            } else {
                break;
            }
        }
        $explode = join(DIRECTORY_SEPARATOR, array_reverse($explode));
        $kernel = "$explode\\$nameKernel";

        if (!class_exists($kernel)) {
            return null;
        }

        $module = $this->app->get($kernel);
        if (!($module instanceof ModuleKernel)) {
            return null;
        }
        return $module;
    }

    /**
     * Set PUT method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function put(string $url, callable|array $action, string $module = ''): Route
    {
        return $this->addRoute([
            'methods' => 'PUT',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * Set OPTIONS method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function options(string $url, callable|array $action, string $module = ''): Route
    {
        return $this->addRoute([
            'methods' => 'OPTIONS',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * Set PATCH method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function patch(string $url, callable|array $action, string $module = ''): Route
    {
        return $this->addRoute([
            'methods' => 'PATCH',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    /**
     * Set DELETE method route
     *
     * @param string $url
     * @param callable|array $action
     * @param string $module
     * @return Route
     * @throws RouteAlreadyExistException
     * @throws RouteNotFoundException
     */
    public function delete(string $url, callable|array $action, string $module = ''): Route
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
    public function post(string $url, callable|array $action, string $module = ''): Route
    {
        return $this->addRoute([
            'methods' => 'POST',
            'url' => $url,
            'action' => $action,
            'module' => $module
        ]);
    }

    public function crud(string $namespace, string $controller, string $moduleName = ''): RouteCrud
    {
        $path = '';
        $action = '';
        $namespaceCrud = $namespace;
        if (strpos($namespace, '.')) {
            $namespaces = explode('.', $namespace);
            $namespace = end($namespaces);
            foreach ($namespaces as $key => $name) {
                if ($key < count($namespaces) - 1) {
                    $path .= $name . '/{' . $name . '}/';
                }
            }
            array_shift($namespaces);
            $action = ucfirst(join('', $namespaces));
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
                'action' => [$controller, 'store' . $action],
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
            'destroy' => [
                'request' => 'delete',
                'url' => "{$path}$namespace{$id}/destroy",
                'action' => [$controller, 'destroy' . $action],
                'name' => "$namespace.destroy",
                'module' => $moduleName
            ],
        ];

        return $this->crudAction($crudActions, $namespaceCrud, $action, $moduleName);
    }

    /**
     * Create all crud model routes
     *
     * @param string $namespace
     * @param string $action
     * @param string $moduleName
     * @return RouteCrud
     */
    public function crudAction(array $crudActions, string $namespace, string $action, string $moduleName = ''): RouteCrud
    {
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

    public function crudApi(string $namespace, string $controller, string $moduleName = ''): RouteCrud
    {
        $path = '';
        $action = '';
        $namespaceCrud = $namespace;
        if (strpos($namespace, '.')) {
            $namespaces = explode('.', $namespace);
            $namespace = end($namespaces);
            foreach ($namespaces as $key => $name) {
                if ($key < count($namespaces) - 1) {
                    $path .= $name . '/{' . $name . '}/';
                }
            }
            array_shift($namespaces);
            $action = ucfirst(join('', $namespaces));
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
            'store' => [
                'request' => 'post',
                'url' => "{$path}$namespace",
                'action' => [$controller, 'store' . $action],
                'name' => "$namespace.store",
                'module' => $moduleName
            ],
            'update' => [
                'request' => 'put',
                'url' => "{$path}$namespace{$id}/update",
                'action' => [$controller, 'update' . $action],
                'name' => "$namespace.update",
                'module' => $moduleName
            ],
            'destroy' => [
                'request' => 'delete',
                'url' => "{$path}$namespace{$id}/destroy",
                'action' => [$controller, 'destroy' . $action],
                'name' => "$namespace.destroy",
                'module' => $moduleName
            ],
        ];

        return $this->crudAction($crudActions, $namespaceCrud, $action, $moduleName);
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
        foreach ($route->getMethods() as $method) {
            $method = strtoupper($method);
            $this->routes[$method] = array_filter($this->routes[$method], fn(Route $r) => $r !== $route);
        }
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $absolute
     * @return string
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws RouteNeedParamsException
     * @throws RouteNotFoundException
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
        $headIndex = array_search('HEAD', $methods);
        unset($methods[$headIndex]);
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
                if (in_array($namespace, $paths)) {
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