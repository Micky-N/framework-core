<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;

class TestCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::REQUIRED, '', 'Test')
            ->addOption('lastName', 'l', InputOption::OPTIONAL, '', false);
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}