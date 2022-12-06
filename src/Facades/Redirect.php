<?php

namespace MkyCore\Facades;

use MkyCore\Abstracts\Facade;


/**
 * @method static \MkyCore\RedirectResponse to(string $to, int $status = 302)
 * @method static \MkyCore\RedirectResponse error(int $code = 404, string $reasonPhrase = '')
 * @method static \MkyCore\RedirectResponse route(string $name, array $params = [], int $status = 302)
 * @method static \MkyCore\RedirectResponse back(int $status = 302)
 * @method static \MkyCore\RedirectResponse session(string $name, string|array $message)
 * @see \MkyCore\RedirectResponse
 */
class Redirect extends Facade
{
    protected static string $accessor = \MkyCore\RedirectResponse::class;
}