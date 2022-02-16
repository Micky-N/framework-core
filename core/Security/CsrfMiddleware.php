<?php


namespace MkyCore\Security;


use MkyCore\Exceptions\CsrfMiddlewareException;
use MkyCore\Facades\Session;
use Psr\Http\Message\ServerRequestInterface;

class CsrfMiddleware implements \MkyCore\Interfaces\MiddlewareInterface
{
    private const LIMIT = 50;
    private const FORM_KEY = '_csrf';
    private const SESSION_KEY = 'csrf';

    /**
     * @inheritDoc
     */
    public function process(callable $next, ServerRequestInterface $request)
    {
        if(in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'DELETE'])){
            $params = $request->getParsedBody() ?? [];
            if(!array_key_exists(self::FORM_KEY, $params)){
                $this->reject('Csrf token is missing', 100);
            }
            $csrfList = Session::get(self::SESSION_KEY, []);
            if(!in_array($params[self::FORM_KEY], $csrfList)){
                $this->reject('Wrong csrf token', 101);
            }
            $this->useToken($params[self::FORM_KEY]);
        }
        return $next($request);

    }

    private function reject(string $message, int $code): void
    {
        throw new CsrfMiddlewareException($message, $code);
    }

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