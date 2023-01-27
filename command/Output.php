<?php

namespace MkyCommand;

use Closure;
use MkyCommand\Exceptions\SectionException;

class Output
{
    use Color;

    /**
     * @var array<string, Section>
     */
    private array $sections = [];

    public function __construct()
    {
    }

    /**
     * @param string|array $message
     * @return void
     */
    public function line(string|array $message): void
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

    /**
     * @param int $maxLine
     * @return Section
     */
    public function section(int $maxLine = 0): Section
    {
        return new Section($maxLine);
    }

    /**
     * Create a new table
     *
     * @return ConsoleTable
     * 
     */
    public function table(): ConsoleTable
    {
        return new ConsoleTable();
    }

    public function progressBar(int|array $elements, ?Closure $closure = null): ProgressBar
    {
        return new ProgressBar($this, $elements, $closure);
    }
}