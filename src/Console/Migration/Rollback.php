<?php

namespace MkyCore\Console\Migration;
use MkyCore\Config;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\Migration\Config\MigrationFile;

class Rollback extends Migration
{
    protected string $direction = 'down';
}