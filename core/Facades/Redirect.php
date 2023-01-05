<?php

namespace MkyCore\Facades;

use MkyCore\Abstracts\Facade;
use MkyCore\RedirectResponse;


/**
 * @method static RedirectResponse to(string $to, int $status = 302)
 * @method static RedirectResponse error(int $code = 404, string $reasonPhrase = '')
 * @method static RedirectResponse route(string $name, array $params = [], int $status = 302)
 * @method static RedirectResponse back(int $status = 302)
 * @method static RedirectResponse session(string $name, string|array $message)
 * @see \MkyCore\RedirectResponse
 */
class Redirect extends Facade
{
    protected static string $accessor = RedirectResponse::class;
}