<?php

namespace MkyCore\Abstracts;

use MkyCore\Validate\Rule;

abstract class AbstractRule
{
    public function __construct(protected mixed $ruleParam = null)
    {
    }

    /**
     * Create your rule
     * callback parameters:
     * 'value', 'data' or/and 'param'
     * you can set 1, 2 or all parameters in rule callback
     *
     * @return Rule
     */
    abstract public function make(): Rule;

    /**
     * Get rule parameter
     *
     * @return mixed
     */
    public function getRuleParam(): mixed
    {
        return $this->ruleParam;
    }

}