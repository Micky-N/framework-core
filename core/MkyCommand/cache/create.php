<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['cache'];
    $directory = 'cache/'.$cli['path'];
    $array = explode('/', $directory);
    $start = '';
    $end = end($array);
    foreach($array as $file){
        $start .= $file;
        if(!file_exists($start)){
            if(stripos($file, '.') != false){
                file_put_contents($start, '');
            }else{
                mkdir($start, 1);
            }
        }
        $start .= '/';
    }

    print("Cache créé.");
}
