<?php

namespace MkyCore\Console\ApplicationCommands\Generate;

use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Str;

class Key extends AbstractCommand
{

    protected string $description = 'Generate a new application key';

    public function execute(Input $input, Output $output): void
    {
        $replace = 1;
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
        $output->success('Key generated successfully', $newKey);
    }
}