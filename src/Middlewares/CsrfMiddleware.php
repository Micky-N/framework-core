<?php


namespace MkyCore\Middlewares;


use Exception;
use MkyCore\Application;
use ReflectionException;
use MkyCore\Config;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\CsrfMiddlewareException;
use MkyCore\Facades\Session;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Request;

class CsrfMiddleware implements MiddlewareInterface
{
    private const LIMIT = 10;
    public const FORM_KEY = '_csrf';
    private const SESSION_KEY = 'csrf';

    public function __construct(private readonly Application $app, private readonly Config $config)
    {

    }

    /**
     * @inheritDoc
     * @param Request $request
     * @param callable $next
     * @return mixed
     * @throws CsrfMiddlewareException
     * @throws ReflectionException
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function process(Request $request, callable $next): mixed
    {
        if($this->config->get('app.security.csrf', false)){
            if($request->post()){
                if(!($csrf = $request->post(self::FORM_KEY))){
                    $this->reject('Csrf token is missing', 100);
                }
                $csrfList = Session::get(self::SESSION_KEY, []);
                if(!in_array($csrf, $csrfList)){
                    $this->reject('Wrong csrf token', 101);
                }
                $this->useToken($csrf);
            }
        }
        if($request->has(self::FORM_KEY)){
            $request = $request->withParsedBody($request->except(self::FORM_KEY));
            $this->app->singleton(Request::class, fn() => $request);
        }
        return $next($request);

    }

    /**
     * @throws CsrfMiddlewareException
     */
    private function reject(string $message, int $code): void
    {
        throw new CsrfMiddlewareException($message, $code);
    }

    /**
     * @throws Exception
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(16));
        $csrfList = Session::get(self::SESSION_KEY, []);
        $csrfList[] = $token;
        Session::set(self::SESSION_KEY, $csrfList);
        $this->limitTokens();
        return $token;
    }

    private function useToken($token): void
    {
        $tokens = array_filter(Session::get(self::SESSION_KEY), function ($tk) use ($token){
            return $tk !== $token;
        });
        Session::set(self::SESSION_KEY, $tokens);
    }

    private function limitTokens(): void
    {
        $tokens = Session::get(self::SESSION_KEY, []);
        if(count($tokens) > self::LIMIT){
            array_shift($tokens);
        }
        Session::set(self::SESSION_KEY, $tokens);
    }
}