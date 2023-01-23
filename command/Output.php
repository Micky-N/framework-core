<?php

namespace MkyCommand;

class Output
{
    use Color;

    public function __construct()
    {
    }

    /**
     * @param string|array $message
     * @return void
     */
    public function writeln(string|array $message): void
    {
        echo "\n";
        for ($i = 0; $i < count($message); $i++) {
            $msg = $message[$i];
            echo $msg . "\n";
        }
    }

    public function write(string $message): void
    {
        echo "\n" . $message;
    }
}