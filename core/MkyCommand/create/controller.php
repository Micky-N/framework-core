<?php

require_once 'vendor/autoload.php';

use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if(php_sapi_name() === "cli"){
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $controllerName = ucfirst($cli['name']);
    $crud = isset($cli['crud']) ? file_get_contents(MickyCLI::BASE_MKY . "/templates/controller/crud." . MickyCLI::EXTENSION) : null;
    $module = isset($cli['module']) ? ucfirst($cli['module']) : null;
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $namespace = sprintf("App%s\\Http\\Controllers%s", ($module ? "\\" . $module : ''), $path ? "\\" . $path : '');
    if(!strpos($controllerName, 'Controller')){
        throw new MkyCommandException("$controllerName controller must have be suffixed by Controller");
    }
    $template = file_get_contents(MickyCLI::BASE_MKY . "/templates/$option." . MickyCLI::EXTENSION);
    $template = str_replace('!name', $controllerName, $template);
    $template = str_replace('!path', $namespace, $template);
    $template = str_replace('!crud', $crud ? "\n" . $crud : '', $template);
    $dir = sprintf("app%s/Http/Controllers%s", ($module ? '/' . $module : ''), ($path ? "/" . $path : ''));
    if(file_exists("$dir/$controllerName.php")){
        throw new MkyCommandException("$controllerName controller already exist");
    }
    if(!is_dir($dir)){
        mkdir($dir, 0777, true);
    }
    $controller = fopen("$dir/$controllerName.php", "w") or die("Unable to open file $controllerName");
    $start = "<" . "?" . "php\n\n";
    fwrite($controller, $start . $template);
    print("$controllerName controller created");
}
