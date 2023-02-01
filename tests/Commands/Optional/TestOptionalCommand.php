<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;

class TestOptionalCommand extends \MkyCommand\AbstractCommand
{

    protected string $description = 'Greet test to someone';

    public function settings(): void
    {
        $this->addOption('name', 'n', InputOption::OPTIONAL, '', false);
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}