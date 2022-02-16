<?php


namespace MkyCore;


use MkyCore\Exceptions\Router\RouteMiddlewareException;
use MkyCore\Facades\Permission;
use MkyCore\Interfaces\MiddlewareInterface;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class RouteMiddleware
{

    private int $index = 0;
    private array $routeMiddlewares;

    public function __construct($appMiddlewares, array $matches)
    {
        $routeMiddlewares = [];
        $middlewares = is_array($appMiddlewares) ? $appMiddlewares : [$appMiddlewares];
        foreach ($middlewares as $middleware) {
            if (stripos($middleware, 'can') === 0) {
                $this->permissionCan($middleware, $matches);
            } else {
                if(is_null(App::getRouteMiddlewares($middleware))){
                    throw new RouteMiddlewareException(sprintf('No middleware found for this alias %s', $middleware));
                }
                $routeMiddlewares[] = App::getRouteMiddlewares($middleware);
            }
        }
        $routeMiddlewares = array_filter($routeMiddlewares);
        $this->routeMiddlewares = $routeMiddlewares;
    }

    /**
     * Run middlewares control
     *
     * @param ServerRequestInterface $request
     * @return mixed|ServerRequestInterface
     */
    public function process(ServerRequestInterface $request)
    {
        if ($this->index < count($this->routeMiddlewares)) {
            $index = $this->index;
            $this->index++;
            $instance = new $this->routeMiddlewares[$index]();
            if (!empty($this->routeMiddlewares[$index]) && $instance instanceof MiddlewareInterface) {
                return call_user_func([$instance, 'process'], [$this, 'process'], $request);
            }
        }
        $this->index = 0;
        return true;
    }

    /**
     * Run permission control
     *
     * @param string $middleware
     * @param array $matches
     * @return bool
     * @throws Exception
     */
    private function permissionCan(string $middleware, array $matches): bool
    {
        $middlewares = explode(':', $middleware);
        $args = explode(',', $middlewares[1]);
        $permission = $args[0];
        $subject = $args[1];
        $params = ["", ucfirst($subject)];
        if(config('structure') === 'HMVC'){
            if(strpos($subject, '/') !== false){
                $subjectArray = explode('/', trim($subject));
                $params = [ucfirst($subjectArray[0])."\\", ucfirst($subjectArray[1])];
                $subject = end($subjectArray);
            }
        }
        $model = sprintf("\\App\\%sModels\\%s", ...$params);
        $subject = $model::find($matches[$subject]);
        return call_user_func([Permission::class, 'can'], $permission, $subject);
    }
}
