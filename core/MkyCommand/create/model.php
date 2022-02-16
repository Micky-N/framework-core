<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;
use MkyCore\MkyCommand\MkyCommandException;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $options = $cli['create'];
    $name = ucfirst($cli['name']);
    $module = isset($cli['module']) ? ucfirst($cli['module']) : null;
    $path = isset($cli['path']) ? ucfirst($cli['path']) : null;
    $table = !empty($cli['table']) ? $cli['table'] : null;
    $pk = !empty($cli['pk']) ? $cli['pk'] : null;
    $namespace = sprintf("App%s\\Models%s", ($module ? "\\" . $module : ''), $path ? "\\" . $path : '');
    $template = file_get_contents(MickyCLI::BASE_MKY."/templates/$options.".MickyCLI::EXTENSION);
    $template = str_replace('!name', $name, $template);
    $template = str_replace('!path', $namespace, $template);
    $template = str_replace('!table', $table ? "protected string \$table = '$table';\n\t" : '' , $template);
    $template = str_replace('!pk', $pk ? "protected string \$primaryKey = '$pk';" : '', $template);
    $dir = "app" . ($module ? '/' . $module : '') . "/Models" . ($path ? "/" . $path : '');
    if (file_exists("$dir/$name.php")) {
        throw new MkyCommandException("$name model already exist");
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); // true for recursive create
    }
    $model = fopen("$dir/$name.php", "w") or die("Unable to open file $name");
    $start = "<"."?"."php\n\n";
    fwrite($model, $start.$template);
    print("$name model created");
}
