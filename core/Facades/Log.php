<?php

namespace MkyCore\Facades;

use Exception;
use Logger;
use MkyCore\Abstracts\Facade;

/**
 * @method static void info(string $message, ?Exception $throwable = null)
 * @method static void debug(string $message, ?Exception $throwable = null)
 * @method static void error(string $message, ?Exception $throwable = null)
 * @method static void fatal(string $message, ?Exception $throwable = null)
 * @method static void off(string $message, ?Exception $throwable = null)
 * @method static void trace(string $message, ?Exception $throwable = null)
 * @method static void warn(string $message, ?Exception $throwable = null)
 * @method static array getLoggers()
 * @method static Logger getLogger(string $name)
 * @method static Logger getCurrentLogger()
 * @method static \MkyCore\Log\Log use(string $logger)
 * @see \MkyCore\Log\Log
 */
class Log extends Facade
{
    protected static string $accessor = \MkyCore\Log\Log::class;
}