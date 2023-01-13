<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCore\Migration\Schema;

class Refresh extends Migration
{
    public function process(): bool|string
    {
        /** @var MigrationFile $migrationRunner */
        $migrationRunner = $this->app->get(MigrationFile::class);
        self::$query = in_array('--query', $this->params);
        $params = $this->parseParams();
        $number = 1;
        $version = null;
        $migrationLogs = [];
        if (!empty($params['-v'])) {
            $version = $params['-v'];
            $migrationLogs = $this->migrationDB->getTo((int)$version);
        } elseif (!empty($params['-n'])) {
            $number = $params['-n'];
            $migrationLogs = $this->migrationDB->getLast($number);
        }
        try {
            if (!$migrationLogs) {
                return false;
            }
            foreach ($migrationLogs as $log) {
                $file = $this->app->get('path:database') . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('down', $file);
            }
            foreach ($migrationLogs as $log) {
                $file = $this->app->get('path:database') . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('up', $file);
            }
            $this->sendResponse(Schema::$SUCCESS, Schema::$ERRORS);
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}