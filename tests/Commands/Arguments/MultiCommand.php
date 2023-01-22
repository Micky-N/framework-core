<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;

class MultiCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addArgument('names', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Array values');
    }

    public function execute(Input $input): mixed
    {
        return true;
    }
}