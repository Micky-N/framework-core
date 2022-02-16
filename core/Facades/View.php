<?php

namespace MkyCore\Facades;

use MkyCore\View as CoreView;


/**
 * @method static \MkyCore\View render(string $view, array $params = [], bool $isModuleView = false)
 *
 * @see \MkyCore\View
 */
class View
{

    /**
     * @var CoreView|null
     */
    public static ?CoreView $view = null;

    public static function __callStatic($method, $arguments)
    {
        if(is_null(self::$view)){
            self::$view = new CoreView();
        }
        return call_user_func_array([self::$view, $method], $arguments);
    }
}