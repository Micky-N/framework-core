<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;

class TestNotFoundCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::REQUIRED, '');
    }

    public function execute(): mixed
    {
        return true;
    }
}