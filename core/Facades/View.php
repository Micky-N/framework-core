<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;

/**
 * @method static \MkyCore\View render(string $view, array $params = [])
 * @method static \MkyCore\View addPath(string $path, string $namespace)
 * @method static string toHtml(string $view, array $params = [])
 * @see \MkyCore\View
 */
class View extends Facade
{
    protected static string $accessor = \MkyCore\View::class;
}
