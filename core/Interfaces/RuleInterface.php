<?php

namespace MkyCore\Interfaces;

use MkyCore\Validate\Rule;

interface RuleInterface
{
    public function make(): Rule;
}