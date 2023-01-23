<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;

class TestNegativeCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::NEGATIVE, '');
    }

    public function execute(): mixed
    {
        return true;
    }
}