<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;

class NoSettingsCommand extends \MkyCommand\AbstractCommand
{

    public function execute(): mixed
    {
        return true;
    }
}