<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Output;

class MultiCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addArgument('names', InputArgument::ARRAY | InputArgument::OPTIONAL, 'Array values');
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}