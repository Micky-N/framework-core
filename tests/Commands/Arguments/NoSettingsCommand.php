<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Output;

class NoSettingsCommand extends \MkyCommand\AbstractCommand
{


    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}