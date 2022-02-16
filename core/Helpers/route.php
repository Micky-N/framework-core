<?php

use MkyCore\Facades\Route;

if(!function_exists('route')){
    function route($routeName, $params = []){
        return Route::generateUrlByName($routeName, $params);
    }
}

if(!function_exists('currentRoute')){
    function currentRoute($route = '', bool $path = false){
        return Route::currentRoute($route, $path);
    }
}

if(!function_exists('namespaceRoute')){
    function namespaceRoute($route = ''){
        return Route::namespaceRoute($route);
    }
}

if(!function_exists('redirectBack')){
    function redirectBack(){
        return 'javascript:history.back()';
    }
}