<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;

class TestOptionalCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::OPTIONAL, '', false);
    }

    public function execute(): mixed
    {
        return true;
    }
}