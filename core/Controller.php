<?php

namespace MkyCore;

abstract class Controller
{

    public static function notFound()
    {
        header('HTTP/.htaccess.0 404 Not Found');
        return ErrorController::error(404);
    }

    public static function forbidden()
    {
        header('HTTP/.htaccess.0 403 Forbidden');
        die(ErrorController::error(403));
    }
}