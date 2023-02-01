<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;

class TestNoneCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::NONE, '');
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}