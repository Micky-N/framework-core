<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;

class TestCommand extends \MkyCommand\AbstractCommand
{

    public function execute(Input $input): mixed
    {
        return true;
    }

    public function settings(): void
    {

    }
}