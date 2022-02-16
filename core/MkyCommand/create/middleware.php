<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $middlewareName = ucfirst($cli['name']);
    $module = isset($cli['module']) ? ucfirst($cli['module']) : null;
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $namespace = sprintf("App%s\\Http\\Middlewares%s", ($module ? "\\" . $module : ''), $path ? "\\" . $path : '');
    $template = file_get_contents(MickyCLI::BASE_MKY."/templates/$option.".MickyCLI::EXTENSION);
    $template = str_replace('!name', $middlewareName, $template);
    $template = str_replace('!path', $namespace, $template);
    $dir = sprintf("app%s/Http/Middlewares%s", ($module ? '/' . $module : ''), ($path ? "/" . $path : ''));
    if (!strpos($middlewareName, 'Middleware')) {
        throw new MkyCommandException("$middlewareName middleware must be suffixed by Middleware");
    }
    if (file_exists("$dir/$middlewareName.php")) {
        throw new MkyCommandException("$middlewareName middleware already exist");
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $middleware = fopen("$dir/$middlewareName.php", "w") or die("Unable to open file $middlewareName");
    $start = "<"."?"."php\n\n";
    fwrite($middleware, $start.$template);
    if(isset($cli['route']) && $cli['route'] === false){
        $middlewareProviderFile = sprintf("app%s/Providers/MiddlewareServiceProvider.php", ($module ? '/' . $module : ''));
        $arr = explode("\n", file_get_contents(dirname(__DIR__)."/../$middlewareProviderFile"));
        $middlewaresLine = array_keys(preg_grep("/'routeMiddlewares' => \[/i", $arr))[0];
        $subname = str_replace('middleware', '', strtolower($middlewareName));
        array_splice($arr, $middlewaresLine + 1, 0, "\t    '$subname' => \\$namespace\\$middlewareName::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        $ptr = fopen(dirname(__DIR__)."/../$middlewareProviderFile", "w");
        fwrite($ptr, $arr);
    }
    print("$middlewareName middleware created");
}
