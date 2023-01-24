<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;

class GreetingCommand extends \MkyCommand\AbstractCommand
{

    protected string $description = 'Say hello to someone';

    public function settings(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Who do you want to greet?')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'The last name');
    }

    public function execute(): mixed
    {
        return $this->input->getArgument('name');
    }
}