<?php

namespace MkyCore\Console;

use MkyCore\Console\Create\Create;
use MkyCore\Console\Show\Module;
use MkyCore\Console\Show\Route;
use MkyCore\Migration\Schema;

trait Color
{

    private static array $COLORS = [
        'blue' => '34',
        'light_blue' => '94',
        'green' => '32',
        'light_green' => '92',
        'red' => '31',
        'light_purple' => '35',
        'yellow' => '33',
        'light_yellow' => '93',
        'gray' => '37',
        'dark_gray' => '90'
    ];
    private static array $BACKGROUNDS = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47',
    ];
    private static array $STYLES = [
        'regular' => '0',
        'bold' => '1',
        'dark' => '2', // this + gray = black
        'underline' => '4',
        'invert' => '7',
        'strike' => '9'
    ];

    protected function getColoredString(string $string, $foreground_color = null, string $style = null, ?string $background_color = null): string
    {
        $colored_string = "";
        $txt_style = 0;

        if (isset(self::$STYLES[strtolower((string)$style)])) {
            $txt_style = self::$STYLES[strtolower((string)$style)];
        }

        // Check if given foreground color found
        if (isset(self::$COLORS[$foreground_color])) {
            $colored_string .= "\e[" . $txt_style . ';' . self::$COLORS[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset(self::$BACKGROUNDS[$background_color])) {
            $colored_string .= "\e[" . $txt_style . ';' . self::$BACKGROUNDS[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\e[0m";
        return $colored_string;
    }

    protected function sendSuccess(string $message, string $res = ''): bool
    {
        echo "\n" . $this->getColoredString($message, 'green', 'bold') . ($res ? ": $res" : '') . "\n";
        return true;
    }

    protected function sendError(string $message, string $res = ''): bool
    {
        echo "\n" . $this->getColoredString($message, 'red', 'bold') . ($res ? ": $res" : '') . "\n";
        return false;
    }

    protected function sendQuestion(string $question, string $default = ''): string
    {
        $message = "\n" . $this->getColoredString($question, 'blue', 'bold');
        if ($default) {
            $message .= $this->getColoredString(" [$default]", 'light_yellow');
        }
        $message .= ":\n";
        echo $message;
        return trim((string)readline("> "));
    }

    /**
     * @return Create
     */
    protected static function getInstance(): Create
    {
        return new self;
    }
}