<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Output;

class ArrayCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('names', 'n', InputArgument::ARRAY, 'Array values', [1, 2]);
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}