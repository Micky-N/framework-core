<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['create'];
    $name = ucfirst($cli['name']);
    $path = "App\\$name";
    $folders = ['Events', 'Http', 'Listeners', 'Models', 'Notifications', 'Providers', 'routes', 'views', 'Voters'];
    $dir = sprintf("app/%s", $name);
    if(is_dir($dir)){
        throw new MkyCommandException("Module $name already exist");
    }
    foreach ($folders as $folder){
        if (!is_dir("$dir/$folder")) {
            mkdir("$dir/$folder", 0777, true);
        }
    }
    foreach (['Controllers', 'Middlewares'] as $folder){
        if (!is_dir("$dir/Http/$folder")) {
            mkdir("$dir/Http/$folder", 0777, true);
        }
    }

    $files = [
        'config' => ['config.php', $dir],
        'eventServiceProvider' => ['EventServiceProvider.php', "$dir/Providers"],
        'middlewareServiceProvider' => ['MiddlewareServiceProvider.php', "$dir/Providers"],
        'functions' => ['functions.php', "$dir/routes"],
        'views' => ['index.mky', "$dir/views"],
        'admin.views' => ['index.mky', "$dir/views/admin"],
        'controller' => [$name.'Controller.php', "$dir/Http/Controllers"],
        'admin.controller' => [$name.'Controller.php', "$dir/Http/Controllers/Admin"],
    ];
    foreach ($files as $key => $file){
        $template = file_exists(MickyCLI::BASE_MKY."/templates/$option/$key.".MickyCLI::EXTENSION) ? file_get_contents(MickyCLI::BASE_MKY."/templates/$option/$key.".MickyCLI::EXTENSION) : '';
        if(!file_exists($file[1])){
            mkdir($file[1], 0777, true);
        }
        $module = fopen("$file[1]/$file[0]", "w") or die("Unable to open folder $file[1]/$file[0]");
        $start = strpos($file[0], '.php') !== false ? "<"."?"."php\n\n" : '';
        fwrite($module, $start.$template);
    }

    $routeFiles = ['web.yaml', 'admin.yaml'];

    foreach ($routeFiles as $routeFile){
        $module = fopen("$dir/routes/$routeFile", "w") or die("Unable to open folder $dir/routes/$routeFile");
    }

    $template = file_get_contents(MickyCLI::BASE_MKY."/templates/$option/module.".MickyCLI::EXTENSION);
    $template = str_replace('!name', "{$name}Module", $template);
    $template = str_replace('!path', $path, $template);
    $module = fopen("$dir/{$name}Module.php", "w") or die("Unable to open folder {$name}Module");
    $start = "<"."?"."php\n\n";
    fwrite($module, $start.$template);

    foreach (['Controllers' => "{$name}Controller", 'Controllers\\Admin' => "Admin/{$name}Controller"] as $namespace => $file){
        $template = file_get_contents(MickyCLI::BASE_MKY."/templates/controller.".MickyCLI::EXTENSION);
        $template = str_replace('!name', "{$name}Controller", $template);
        $template = str_replace('!crud', '', $template);
        $template = str_replace('!path', "App\\$name\\Http\\$namespace", $template);
        $controller = fopen("$dir/Http/Controllers/{$file}.php", "w") or die("Unable to open folder {$file}");
        $start = "<"."?"."php\n\n";
        fwrite($controller, $start.$template);
    }

    $moduleServiceProviderFile = "app/Providers/ModuleServiceProvider.php";
    $arr = explode("\n", file_get_contents(dirname(__DIR__) . "/../$moduleServiceProviderFile"));
    $moduleLine = array_keys(preg_grep("/return \[/i", $arr))[0];
    array_splice($arr, $moduleLine + 1, 0, "\t\\App\\$name\\{$name}Module::class,");
    $arr = array_values($arr);
    $arr = implode("\n", $arr);
    $ptr = fopen(dirname(__DIR__) . "/../$moduleServiceProviderFile", "w");
    fwrite($ptr, $arr);

    print("Module $name created");
}