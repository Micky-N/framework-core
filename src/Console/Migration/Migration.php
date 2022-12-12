<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCore\Config;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\Migration\Config\MigrationFile;

abstract class Migration extends AbstractCreate
{
    public static bool $query = false;
    protected string $direction = '';

    public function process(): bool|string
    {
        $configDatabase = $this->app->get(Config::class)->get('database');
        $default = $configDatabase['default'];
        $config = $configDatabase['connections'][$default];
        $migrationRunner = new MigrationFile($this->app, $config);
        self::$query = in_array('--query', $this->params);
        try {
            $migrationRunner->actionMigration($this->direction);
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}