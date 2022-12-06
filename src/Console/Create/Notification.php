<?php

namespace MkyCore\Console\Create;

class Notification extends Create
{
    protected string $outputDirectory = 'Notifications';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:Notification'],
    ];
}