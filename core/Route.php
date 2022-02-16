<?php

namespace MkyCore;

use Closure;
use MkyCore\Security\CsrfMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Route
{
    private string $path;
    /**
     * @var array|callable
     */
    private $action;

    private string $name;
    /**
     * @var string|array
     */
    private $middleware;

    private array $matches;
    private ?string $module;

    /**
     * @param string $path
     * @param array|callable $action
     * @param string $name
     * @param null|array|string $middleware
     * @param string|null $module
     */
    public function __construct(string $path, $action, string $name = '', $middleware = null, string $module = null)
    {
        $this->path = '/' . trim($path, '/');
        $this->action = $action;
        $this->name = $name;
        $this->middleware = $middleware;
        $this->module = $module;
    }

    /**
     * Control route and run route callback
     *
     * @param ServerRequestInterface $request
     * @return void|bool
     * @throws Exceptions\Router\RouteMiddlewareException
     */
    public function execute(ServerRequestInterface $request)
    {
        $params = [];
        if(config('structure') && config('structure') === 'HMVC' && !is_null($this->module)){
            App::setCurrentModule(new $this->module());
            App::setApplication();
        }
        if($this->matches){
            $params = $this->matches;
        }
        if(!is_null($request->getParsedBody()) && array_key_exists('_csrf', $request->getParsedBody())){
            App::setRouteMiddleware('csrf', CsrfMiddleware::class);
            if(!empty($this->middleware)){
                $this->middleware = is_string($this->middleware) ? [$this->middleware] : $this->middleware;
                $this->middleware[] = 'csrf';
            } else {
                $this->middleware = 'csrf';
            }
        }
        if(!empty($this->middleware)){
            $routeMiddleware = new RouteMiddleware($this->middleware, $this->matches);
            if(!$routeMiddleware->process($request)){
                return false;
            }
        }
        if($request->getParsedBody()){
            $params[] = $request->getParsedBody();
            foreach ($params as $k => $param) {
                if(!is_array($param)){
                    $params[$k] = htmlspecialchars($param);
                }else{
                    foreach($param as $key => $value){
                        $param[$k] = htmlspecialchars($value);
                    }
                }
            }
        }
        if($request->getQueryParams()){
            $queryParams = $request->getQueryParams();
            foreach ($queryParams as $k => $queryParam) {
                $queryParams[$k] = htmlspecialchars($queryParam);
            }
            $request->withQueryParams($queryParams);
        }
        if(is_array($this->action)){
            $controller = new $this->action[0]();
            $method = $this->action[1];
            $this->routesDebugBar($request, $params, $controller, $method, $this->module);
            return call_user_func_array([$controller, $method], $params);
        } else if(is_callable($this->action)){
            $this->routesDebugBar($request, $params, $this->action, null, $this->module);
            return call_user_func_array($this->action, $params);
        }
    }

    public function setPrefix(string $prefix)
    {
        if(strpos($this->path, $prefix) === false){
            $this->path = $prefix . '/' . trim($this->path, '/');
        }
        return $this;
    }

    /**
     * Check if url match wih one route and get params
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function match(ServerRequestInterface $request)
    {
        $url = trim($request->getUri()->getPath(), '/');
        $path = preg_replace('#(:[\w]+)#', '([^/]+)', trim($this->path, '/'));

        $pathToMatch = "#^$path$#";
        if(preg_match($pathToMatch, $url, $matches)){
            $key = array_map(function ($pa) {
                if(strpos($pa, ':') !== false){
                    preg_match('/(.*)?:(.*)?/', $pa, $matches);
                    return $matches[2];
                }
                return null;
            }, explode('/', trim($this->path, '/')));
            $key = array_filter($key);
            array_shift($matches);
            $matches = $matches != [] ? array_combine($key, $matches) : $matches;
            $this->matches = $matches;
            return true;
        }
        return false;
    }

    /**
     * Get route name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route middleware
     *
     * @return array|string
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get route uri
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get route action
     *
     * @return array|callable
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Render the route to debugBar
     *
     * @param ServerRequestInterface $request
     * @param array $params
     * @param Controller|Closure $controller
     * @param string|null $method
     * @param string|null $module
     */
    public function routesDebugBar(ServerRequestInterface $request, array $params, $controller, string $method = null, string $module = null)
    {
        if(config('env') && strtoupper(config('env')) === 'LOCAL'){
            if($module){
                $module = explode('\\', $module);
                $module = $module[1];
            }
            $array = array_filter([
                'url' => $request->getUri()->getPath(),
                'params' => $params,
                'controller' => get_class($controller),
                'method' => $method,
                'module' => $module
            ]);
            \MkyCore\Facades\StandardDebugBar::addMessage('Routes', $array);
        }
        return null;
    }

    public function __get($name)
    {
        if(isset($this->{$name})){
            return $this->{'get' . ucfirst($name)}();
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }
}
