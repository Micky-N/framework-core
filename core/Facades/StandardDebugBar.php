<?php


namespace MkyCore\Facades;

use MkyCore\StandardDebugBar as CoreStandardDebugBar;


/**
 * @method static \MkyCore\StandardDebugBar addMessage(string $collector, $message, $type = 'info')
 * @method static string|null render()
 * @method static string|null renderhead()
 *
 * @see \MkyCore\StandardDebugBar
 */
class StandardDebugBar
{
    /**
     * @var CoreStandardDebugBar|null
     */
    public static ?CoreStandardDebugBar $debugbar;

    public static function __callStatic($method, $arguments)
    {
        if(empty(self::$debugbar)){
            self::$debugbar = new CoreStandardDebugBar();
        }
        return call_user_func_array([self::$debugbar, $method], $arguments);
    }
}