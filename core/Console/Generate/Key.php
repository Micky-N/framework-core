<?php

namespace MkyCore\Console\Generate;

use MkyCore\Console\Create\Create;
use MkyCore\Str;

class Key extends Create
{
    public function process(): bool|string
    {
        $replace = 1;
        $envs = $_ENV;
        $newKey = Str::random();
        $explodedFile = explode("\n", file_get_contents('.env'));
        $line = preg_grep('/APP_KEY=/', $explodedFile);
        if($line){
            $index = key($line);
        }else{
            $apps = preg_grep('/APP_[A-Z]+=/', $explodedFile);
            $index = count($apps);
            $replace = 0;
        }
        array_splice($explodedFile, $index, $replace, "APP_KEY=$newKey");
        file_put_contents('.env', join("\n", $explodedFile));
        return $this->sendSuccess('Key generated successfully', $newKey);
    }
}