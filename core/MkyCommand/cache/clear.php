<?php

require_once 'vendor/autoload.php';
use MkyCore\MKYCommand\MickyCLI;

if (php_sapi_name() === "cli") {
    $cli = getopt('', MickyCLI::cliLongOptions());
    $option = $cli['cache'];
    $directory = isset($cli['path']) ? 'cache/' . $cli['path'] : 'cache';

    function recursiveRemove($dir) {
        $structure = glob(rtrim($dir, "/").'/*');
        if (is_array($structure) ) {
            foreach($structure as $file) {
                if (is_dir($file)) recursiveRemove($file);
                elseif (is_file($file)) unlink($file);
            }
        }
        if(stripos($dir, '/') != false){
            if(is_dir($dir)){
                rmdir($dir);
            }else{
                unlink($dir);
            }
        }
            
    }
    recursiveRemove($directory); 

    print("Cache nettoyé.");
}
