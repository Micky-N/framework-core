<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Output;

class ArrayCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addArgument('names', InputArgument::ARRAY, 'Array values');
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}