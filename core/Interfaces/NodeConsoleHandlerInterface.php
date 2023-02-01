<?php

namespace MkyCore\Interfaces;

use MkyCommand\Input;

interface NodeConsoleHandlerInterface
{
    public function handle(Input $input): static;
}