<?php

namespace MkyCore\Interfaces;

interface ViewCompileInterface
{

    /**
     * View compile template
     * @param string $view
     * @param array $params
     * @return string
     */
    public function compile(string $view, array $params = []): string;
}