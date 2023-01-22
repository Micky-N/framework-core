<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;

class TestNoneCommand extends \MkyCommand\AbstractCommand
{

    public function settings()
    {
        $this->addOption('name', 'n', InputOption::NONE, '');
    }

    public function execute(Input $input): mixed
    {
        return true;
    }
}