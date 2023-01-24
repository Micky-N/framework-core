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
        $this->addOption('test', '-t', Input\InputOption::ARRAY | Input\InputOption::REQUIRED, 'Test array named', ['name', 'fdfd', 5]);
    }

    public function execute(): mixed
    {
        return $this->input->getArgument('name');
    }
}