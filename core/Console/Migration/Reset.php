<?php

namespace MkyCore\Console\Migration;

class Reset extends Migration
{
    protected string $direction = 'up';

    public function process(): bool|string
    {
        $pop = in_array('--pop', $this->params);
        exec('php mky migration:clear', $output);
        for ($i = 0; $i < count($output); $i++) {
            echo $output[$i]."\n";
        }
        exec('php mky migration:run'.($pop ? ' --pop' : ''), $output);
        for ($i = 0; $i < count($output); $i++) {
            echo $output[$i]."\n";
        }
        return true;
    }
}