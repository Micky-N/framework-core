<?php

namespace MkyCore\Console\Migration;
use MkyCore\Config;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\Migration\Config\MigrationFile;

class Run extends Migration
{
    protected string $direction = 'up';
}