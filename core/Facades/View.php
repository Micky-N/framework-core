<?php

namespace MkyCore\Facades;


use MkyCore\Abstracts\Facade;
use MkyEngine\DirectoryLoader;

/**
 * @method static \MkyCore\View render(string $view, array $params = [])
 * @method static \MkyCore\View addPath(string $namespace, DirectoryLoader $loader)
 * @method static string toHtml(string $view, array $params = [])
 * @see \MkyCore\View
 */
class View extends Facade
{
    protected static string $accessor = \MkyCore\View::class;
}
