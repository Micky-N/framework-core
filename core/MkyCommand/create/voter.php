<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $model = '\\'.trim($cli['model'], '\\');
    $modelArray = explode('\\', strtolower($model));
    $modelLower = end($modelArray);
    $name = ucfirst($cli['name']);
    $module = isset($cli['module']) ? ucfirst($cli['module']) : null;
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $namespace = sprintf("App%s\\Voters%s", ($module ? "\\" . $module : ''), $path ? "\\" . $path : '');
    $action = isset($cli['action']) ? strtoupper($cli['action']): null;
    $actionLower = $action ? strtolower($action) : null;
    if (!strpos($name, 'Voter')) {
        throw new MkyCommandException("$name must be suffixed by Voter");
    }
    $template = file_get_contents(MickyCLI::BASE_MKY."/templates/$option.".MickyCLI::EXTENSION);
    $template = str_replace('!name', $name, $template);
    $template = str_replace('!modelLower', "\$$modelLower", $template);
    $template = str_replace('!path', $namespace, $template);
    $template = str_replace('!model', $model, $template);
    $template = str_replace('!action', $action ? "const $action = '$actionLower';" : '', $template);
    $dir = sprintf("app%s/Voters%s", ($module ? '/' . $module : ''), ($path ? "/" . $path : ''));
    if (file_exists("$dir/$name.php")) {
        throw new MkyCommandException("$name voter already exist");
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $voter = fopen("$dir/$name.php", "w") or die("Unable to open file $name");
    $start = "<"."?"."php\n\n";
    fwrite($voter, $start.$template);
    $middlewareProviderFile = sprintf("app%s/Providers/MiddlewareServiceProvider.php", ($module ? '/' . $module : ''));;
    $arr = explode("\n", file_get_contents(dirname(__DIR__)."/../$middlewareProviderFile"));
    $votersLine = array_keys(preg_grep("/'voters' => \[/i", $arr))[0];
    array_splice($arr, $votersLine + 1, 0, "\t    \\$namespace\\$name::class,");
    $arr = array_values($arr);
    $arr = implode("\n", $arr);
    $ptr = fopen(dirname(__DIR__)."/../$middlewareProviderFile", "w");
    fwrite($ptr, $arr);
    print("$name voter created");
}
