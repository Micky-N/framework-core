<?php

namespace MkyCore\Console\Migration;

class Refresh extends Migration
{
    protected string $direction = 'up';

    public function process(): bool|string
    {
        $pop = in_array('--pop', $this->params);
        exec('php mky migration:clear', $outputClear);
        for ($i = 0; $i < count($outputClear); $i++) {
            echo $outputClear[$i] . "\n";
        }
        exec('php mky migration:run' . ($pop ? ' --pop' : ''), $outputRun);
        for ($i = 0; $i < count($outputRun); $i++) {
            echo $outputRun[$i] . "\n";
        }
        return true;
    }
}