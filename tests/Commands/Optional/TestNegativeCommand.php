<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;

class TestNegativeCommand extends \MkyCommand\AbstractCommand
{

    public function settings()
    {
        $this->addOption('name', 'n', InputOption::NEGATIVE, '');
    }

    public function execute(Input $input): mixed
    {
        return true;
    }
}