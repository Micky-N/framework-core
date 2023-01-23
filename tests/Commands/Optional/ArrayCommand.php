<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;

class ArrayCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('names', 'n', InputArgument::IS_ARRAY, 'Array values', [1, 2]);
    }

    public function execute(): mixed
    {
        return true;
    }
}