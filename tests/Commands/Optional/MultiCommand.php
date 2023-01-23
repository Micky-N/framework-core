<?php

namespace MkyCore\Tests\Commands\Optional;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;

class MultiCommand extends \MkyCommand\AbstractCommand
{

    public function settings(): void
    {
        $this->addOption('names', 'n', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Array values', ['Test']);
    }

    public function execute(): mixed
    {
        return true;
    }
}