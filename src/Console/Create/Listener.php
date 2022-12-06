<?php

namespace Console\Create;

class Listener extends Create
{
    protected string $outputDirectory = 'Listeners';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:Listener'],
    ];
}