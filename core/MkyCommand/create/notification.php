<?php

require_once 'vendor/autoload.php';

use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if(php_sapi_name() === "cli"){
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $via = $cli['via'];
    $tovia = 'to' . ucfirst($via);
    $name = ucfirst($cli['name']);
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $namespace = sprintf("App\\Notifications%s", $path ? "\\" . $path : '');
    if(!strpos($name, 'Notification')){
        throw new MkyCommandException("$name notification must be suffixed by Notification");
    }
    $template = file_get_contents(MickyCLI::BASE_MKY . "/templates/$option." . MickyCLI::EXTENSION);
    $template = str_replace('!name', $name, $template);
    $template = str_replace('!path', $namespace, $template);
    $template = str_replace('!via', "'$via'", $template);
    $template = str_replace('!tovia', $tovia, $template);
    $dir = sprintf("app/Notifications%s", ($path ? "/" . $path : ''));
    if(file_exists("$dir/$name.php")){
        throw new MkyCommandException("$name notification already exist");
    }
    if(!is_dir($dir)){
        mkdir($dir, 0777, true);
    }
    $notification = fopen("$dir/$name.php", "w") or die("Unable to open file $name");
    $start = "<" . "?" . "php\n\n";
    fwrite($notification, $start . $template);
    print("$name notification created");
}
