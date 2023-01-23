<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;

class TestNoneCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::NONE, '');
    }

    public function execute(): mixed
    {
        return true;
    }
}