<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $name = ucfirst($cli['name']);
    $module = isset($cli['module']) ? ucfirst($cli['module']) : null;
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $namespace = sprintf("App%s\\Events%s", ($module ? "\\" . $module : ''), $path ? "\\" . $path : '');
    if (!strpos($name, 'Event')) {
        throw new MkyCommandException("$name event must be suffixed by Event");
    }
    $template = file_get_contents(MickyCLI::BASE_MKY."/templates/$option.".MickyCLI::EXTENSION);
    $template = str_replace('!path', $namespace, $template);
    $template = str_replace('!name', $name, $template);
    $dir = sprintf("app%s/Events%s", ($module ? '/' . $module : ''), ($path ? "/" . $path : ''));
    if (file_exists("$dir/$name.php")) {
        throw new MkyCommandException("$name event already exist");
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $event = fopen("$dir/$name.php", "w") or die("Unable to open file $name");
    $start = "<"."?"."php\n\n";
    fwrite($event, $start.$template);
    print("$name event created");
}
