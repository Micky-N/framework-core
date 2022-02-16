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
    $namespace = sprintf("App%s\\Listeners%s", ($module ? "\\" . $module : ''), $path ? "\\" . $path : '');
    if (!strpos($name, 'Listener')) {
        throw new MkyCommandException("$name listener must be suffixed by Listener");
    }
    $template = file_get_contents(MickyCLI::BASE_MKY."/templates/$option.".MickyCLI::EXTENSION);
    $template = str_replace('!name', $name, $template);
    $template = str_replace('!path', $namespace, $template);
    $dir = sprintf("app%s/Listeners%s", ($module ? '/' . $module : ''), ($path ? "/" . $path : ''));
    if (file_exists("$dir/$name.php")) {
        throw new MkyCommandException("$name listener already exist");
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $listener = fopen("$dir/$name.php", "w") or die("Unable to open folder $name");
    $start = "<"."?"."php\n\n";
    fwrite($listener, $start.$template);
    print("$name listener created");
}
