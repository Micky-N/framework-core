<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Output;

class MultiCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('names', 'n', InputArgument::ARRAY | InputArgument::OPTIONAL, 'Array values', ['Test']);
    }

    public function execute(Input $input, Output $output): mixed
    {
        return true;
    }
}