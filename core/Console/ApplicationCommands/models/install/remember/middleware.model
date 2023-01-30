<?php

namespace App\Middlewares;

use Exception;
use MkyCore\AuthManager;
use MkyCore\Facades\Cookie;
use MkyCore\Interfaces\MiddlewareInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Remember\RememberMe;
use MkyCore\Request;

class RememberTokenMiddleware implements MiddlewareInterface
{

    public function process(Request $request, callable $next): ResponseHandlerInterface
    {
        if ($rememberToken = Cookie::get(RememberMe::PREFIX_ID)) {
            try {
                if (!($rememberTokenEntity = RememberMe::getRememberTokenEntity($rememberToken))) {
                    return $next($request);
                }
                $user = $rememberTokenEntity->user();
                /** @var AuthManager $authManager */
                $authManager = app()->get(AuthManager::class);
                $authManager->use($rememberTokenEntity->provider())->login($user);
            } catch (Exception $e) {
            }
        }
        return $next($request);
    }
}