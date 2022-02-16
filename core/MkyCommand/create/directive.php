<?php

require_once 'vendor/autoload.php';

use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if(php_sapi_name() === "cli"){
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $name = ucfirst($cli['name']);
    $function = $cli['fn'];
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $namespace = sprintf("App\\MkyDirectives%s", $path ? "\\" . $path : '');
    if(!strpos($name, 'Directive')){
        throw new MkyCommandException("$name must be suffixed by Directive");
    }
    $template = file_get_contents(MickyCLI::BASE_MKY . "/templates/$option." . MickyCLI::EXTENSION);
    $template = str_replace('!name', $name, $template);
    $template = str_replace('!function', $function, $template);
    $template = str_replace('!path', $namespace, $template);

    $dir = sprintf("app/MkyDirectives%s", ($path ? "/" . $path : ''));
    if(file_exists("$dir/$name.php")){
        throw new MkyCommandException("$name directive already exist");
    }
    if(!is_dir($dir)){
        mkdir($dir, 0777, true);
    }
    $directive = fopen("$dir/$name.php", "w") or die("Unable to open file $name");
    $start = "<" . "?" . "php\n\n";
    fwrite($directive, $start . $template);
    $mkyServiceProviderFile = "app/Providers/MkyServiceProvider.php";
    $arr = explode("\n", file_get_contents(dirname(__DIR__) . "/../$mkyServiceProviderFile"));
    $directiveLine = array_keys(preg_grep("/'directives' => \[/i", $arr))[0];
    array_splice($arr, $directiveLine + 1, 0, "\t    new \\$namespace\\$name(),");
    $arr = array_values($arr);
    $arr = implode("\n", $arr);
    $ptr = fopen(dirname(__DIR__) . "/../$mkyServiceProviderFile", "w");
    fwrite($ptr, $arr);
    print("$name directive created");
}
