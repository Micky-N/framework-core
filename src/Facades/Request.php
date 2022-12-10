<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;

/**
 * @method static array|string header(string $name, string $default = null)
 * @method static \Carbon\Carbon|null date(string $name, string $format = 'Y-m-d H:i:s', string $timezone = 'Europe/Paris')
 * @method static mixed post(string $name = null, string|int $default = null)
 * @method static mixed query(string $name = null, string|int $default = null)
 * @method static mixed input(string $name = null, string|int $default = null)
 * @method static array|null only(array|string $attributes, string $type = 'post')
 * @method static array|null except(array|string $attributes, string $type = 'post')
 * @method static mixed cookie(string $name, mixed $default = null)
 * @method static mixed session(string $name = null, mixed $default = null)
 * @method static mixed flash(string $name, mixed $default = null)
 * @method static bool hasFlash(string $name)
 * @method static mixed old(string $name, mixed $default = null)
 * @method static bool hasOld(string $name)
 * @method static bool isMethod(string $methodSearch)
 * @method static string method()
 * @method static bool|null boolean(string $name)
 * @method static bool is(string $routeRegex)
 * @method static bool routeIs(string $routeNameRegex)
 * @method static array parameters()
 * @method static string fullUriWithQuery()
 * @method static string fullUri()
 * @method static string baseUri()
 * @method static string|null backUrl()
 * @method static string scheme()
 * @method static string host()
 * @method static string path()
 * @method static \MkyCore\Request addQuery(array $queries)
 * @method static string|null bearerToken()
 * @method static \MkyCore\AuthManager auth()
 * @method static string ip()
 * @method static \MkyCore\RedirectResponse|bool validate(array $rules)
 * @see \MkyCore\Request
 */
class Request extends Facade
{
    protected static string $accessor = \MkyCore\Request::class;
}