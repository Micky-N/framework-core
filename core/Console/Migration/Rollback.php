<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;

class Rollback extends Migration
{
    public function process(): bool|string
    {
        /** @var MigrationFile $migrationRunner */
        $migrationRunner = $this->app->get(MigrationFile::class);
        self::$query = in_array('--query', $this->params);
        $params = $this->parseParams();
        $number = $params['-n'] ?? 1;
        $version = $params['-v'] ?? null;
        $migrationLogs = [];
        if ($version) {
            $version = (int)$version;
            $migrationLogs = $this->migrationDB->getTo($version);
        } elseif ($number) {
            $migrationLogs = $this->migrationDB->getLast($number);
        }
        try {
            if (!$migrationLogs) {
                return false;
            }
            foreach ($migrationLogs as $log) {
                $file = $this->app->get('path::database') . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('down', $file);
            }
            $this->sendResponse(Schema::$SUCCESS, Schema::$ERRORS);
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}