<?php

namespace MkyCore;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Router\Route;
use MkyCore\Validate\Validator;


class Request extends ServerRequest implements ServerRequestInterface
{

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';
    const METHOD_KEY_FORM = '_method';

    const TYPE_DATA = ['query', 'post'];

    private static ?ServerRequestInterface $_instance = null;

    public static function fromGlobals(): ServerRequestInterface|static
    {
        if (is_null(self::$_instance)) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $headers = getallheaders();
            $uri = self::getUriFromGlobals();
            $body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

            $serverRequest = new Request($method, $uri, $headers, $body, $protocol, $_SERVER);
            self::$_instance = $serverRequest
                ->withCookieParams($_COOKIE)
                ->withQueryParams($_GET)
                ->withParsedBody($_POST)
                ->withUploadedFiles(self::normalizeFiles($_FILES));
        }
        return self::$_instance;
    }

    public function header(string $name = null, string $default = null): array|string|null
    {
        if($name){
            return $this->getHeader($name) ?? $default;
        }
        return $this->getHeaders();
    }

    public function date(string $name, string $format = 'Y-m-d H:i:s', string $timezone = 'Europe/Paris'): ?Carbon
    {
        $date = null;
        if ($this->post($name)) {
            $date = $this->post($name);
        } elseif ($this->query($name)) {
            $date = $this->query($name);
        }
        if ($date) {
            $date = Carbon::createFromFormat($format, $date, $timezone);
        }
        return $date;
    }

    public function post(string $name = null, mixed $default = null): mixed
    {
        return $this->getRequestData($name, $this->getParsedBody(), $default);
    }

    public function query(string $name = null, mixed $default = null): mixed
    {
        return $this->getRequestData($name, $this->getQueryParams(), $default);
    }
    
    private function getRequestData(string $name = null, array $data, $default = null): mixed
    {
        $queryParams = $data;
        if ($name) {
            return $queryParams[$name] ?? $default;
        }
        return $queryParams;
    }

    public function input(string $name = null, mixed $default = null): mixed
    {
        if ($res = $this->post($name)) {
            return $res;
        } elseif ($res = $this->query($name)) {
            return $res;
        } else {
            return $default;
        }
    }

    public function has(string $attribute, string $type = 'post'): bool
    {
        if (!in_array(strtolower($type), self::TYPE_DATA)) {
            return false;
        }
        return $this->{$type}($attribute) !== null;
    }

    public function only(array|string $attributes, string $type = 'post'): ?array
    {
        if (!in_array(strtolower($type), self::TYPE_DATA)) {
            return null;
        }
        $retrieveAttributes = $this->{$type}();
        return array_filter($retrieveAttributes, fn($attribute) => in_array($attribute, (array)$attributes), ARRAY_FILTER_USE_KEY);
    }

    public function except(array|string $attributes, string $type = 'post'): ?array
    {
        if (!in_array(strtolower($type), self::TYPE_DATA)) {
            return null;
        }
        $retrieveAttributes = $this->{$type}();
        return array_filter($retrieveAttributes, fn($attribute) => !in_array($attribute, (array)$attributes), ARRAY_FILTER_USE_KEY);
    }

    public function cookie(string $name, mixed $default = null): mixed
    {
        $cookies = $this->getCookieParams();
        return $cookies[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function session(string $name, mixed $default = null): mixed
    {
        return \MkyCore\Facades\Session::get($name, $default);
    }

    public function isMethod(string $methodSearch): bool
    {
        $method = $this->method();
        return strtolower($method) === strtolower($methodSearch);
    }

    public function method(): string
    {
        return $this->getMethod();
    }

    public function boolean(string $name): ?bool
    {
        $bool = null;
        if ($this->post($name)) {
            $bool = $this->post($name);
        } elseif ($this->query($name)) {
            $bool = $this->query($name);
        }
        if (!is_null($bool)) {
            $bool = (bool)$bool;
        }
        return $bool;
    }

    private function currentRoute()
    {
        return $this->getAttribute(Route::class);
    }

    public function is(string $routeRegex): bool
    {
        $routeRegex = '/^' . str_replace('/', '\/', $routeRegex) . '/';
        $route = $this->currentRoute();
        $url = $route->getUrl();
        return (bool)preg_match($routeRegex, $url);
    }

    public function routeIs(string $routeNameRegex): bool
    {
        $routeNameRegex = '/^' . str_replace('/', '\/', $routeNameRegex) . '/';
        $route = $this->currentRoute();
        $name = $route->getName();
        return (bool)preg_match($routeNameRegex, $name);
    }

    public function parameters(): array
    {
        return $this->currentRoute()->getParams();
    }
    
    public function parameter(string $key): mixed
    {
        return $this->currentRoute()->getParams()[$key] ?? null;
    }

    public function fullUriWithQuery(): string
    {
        return sprintf("%s?%s", $this->fullUri(), $this->getUri()->getQuery());
    }

    public function fullUri(): string
    {
        return sprintf("%s://%s%s", $this->scheme(), $this->host(), $this->path());
    }

    public function baseUri(): string
    {
        return sprintf("%s://%s", $this->scheme(), $this->host());
    }

    public function scheme(): string
    {
        return $this->getUri()->getScheme();
    }

    public function host(): string
    {
        return $this->getUri()->getHost();
    }

    public function path(): string
    {
        return $this->getUri()->getPath();
    }

    public function addQuery(array $queries): Request
    {
        $oldQuery = $this->query();
        $queries = array_replace_recursive($oldQuery, $queries);
        $queryString = http_build_query($queries);

        return new static($this->method(), $this->fullUri() . '?' . $queryString, $this->getHeaders());
    }

    public function bearerToken(): string|null
    {
        return $this->getAttribute('Authorization');
    }

    /**
     * @return mixed
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function auth(): AuthManager
    {
        return app()->get(\MkyCore\AuthManager::class);
    }

    public function ip(): string
    {
        return $this->server('REMOTE_ADDR');
    }

    /**
     * @throws Exception
     */
    public function validate(array $rules, array $messages = []): RedirectResponse|bool
    {
        $validate = new Validator($this->post(), $rules, $messages);
        if($validate->passed()){
            return true;
        }
        $response = redirect()->back()->session('error', $validate->getErrors());
        $response->handle()->send();
        return $response;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->getServerParams()[$key] ?? $default;
    }

    public function backUrl(): string|null
    {
        return $this->server('HTTP_REFERER');
    }
}