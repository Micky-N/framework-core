<?php

namespace MkyCore\Tests\Commands\Arguments;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Output;

class GreetingCommand extends \MkyCommand\AbstractCommand
{

    protected string $description = <<<'EOD'
Example of string spanning multiple lines
using nowdoc syntax. Backslashes are always treated literally,
e.g. \\ and \'.
EOD;

    public function settings(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Who do you want to greet?')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'The last name');
        $this->addOption('test', '-t', Input\InputOption::ARRAY | Input\InputOption::REQUIRED, 'Test array named', ['name', 'fdfd', 5]);
    }

    public function execute(Input $input, Output $output): mixed
    {
        return $input->argument('name');
    }
}