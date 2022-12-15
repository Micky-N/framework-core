<?php

namespace MkyCore\Console\Migration;

class Run extends Migration
{
    protected string $direction = 'up';
    
    public function process(): bool|string
    {
        $pop = in_array('--pop', $this->params);
        $parent = parent::process();
        if($parent && $pop){
            exec('php mky populator:run', $output);
            for($i = 0; $i < count($output); $i++){
                echo $output[$i];
            }
        }
        return $parent;
    }
}