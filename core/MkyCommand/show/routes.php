<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;
defined('ROOT') or define('ROOT', dirname(dirname(__DIR__))."/");
if (php_sapi_name() === "cli") {
    \MkyCore\App::ConfigInit();
    $cli = getopt('', MickyCLI::cliLongOptions());
    $request = isset($cli['request']) ? $cli['request'] : null;
    $controller = isset($cli['controller']) ? $cli['controller'] : null;
    $routes = MkyCore\Facades\Route::toArray();
    if($request){
        $routes = array_filter($routes, function($route)use($request){
            return $route[0] == strtoupper($request);
        });
    }
    if($controller){
        $routes = array_filter($routes, function($route)use($controller){
            return $route[2] == $controller;
        });
    }
    $fields = ['request', 'path', 'controller', 'method', 'name', 'middleware'];
    if(config('structure') === 'HMVC'){
        $fields[] = 'module';
    }
    $array = [$fields];
    array_push($array, ...$routes);
    echo MickyCLI::table($array);
}
