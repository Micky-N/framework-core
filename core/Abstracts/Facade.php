<?php

namespace MkyCore\Abstracts;


use Exception;
use ReflectionException;

abstract class Facade
{
    protected static string $accessor = '';
    private static array $_instance = [];

    /**
     * Call statically class method
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public static function __callStatic(string $name, mixed $arguments)
    {
        if (empty(static::$_instance[static::getAccessor()])) {
            static::$_instance[static::getAccessor()] = app()->get(static::getAccessor());
        }
        return call_user_func_array([static::$_instance[static::getAccessor()], $name], $arguments);
    }

    /**
     * Get class accessor
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return static::$accessor;
    }
}