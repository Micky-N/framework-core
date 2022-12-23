<?php

namespace MkyCore\Interfaces;

use MkyCore\Validate\Rule;

interface RuleInterface
{
    /**
     * @return Rule
     */
    public function make(): Rule;
}