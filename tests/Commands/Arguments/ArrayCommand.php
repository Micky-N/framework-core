<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;

class ArrayCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addArgument('names', InputArgument::IS_ARRAY, 'Array values');
    }

    public function execute(Input $input): mixed
    {
        return true;
    }
}