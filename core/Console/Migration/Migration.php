<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;

abstract class Migration extends AbstractCreate
{
    public static bool $query = false;
    protected string $direction = '';

    public function process(): bool|string
    {
        $migrationRunner = new MigrationFile($this->app);
        self::$query = in_array('--query', $this->params);
        $params = $this->parseParams();
        $file = $params['--file'] ?? null;
        try {
            $migrationRunner->actionMigration($this->direction, $file);
            if ($success = Schema::$SUCCESS) {
                for ($i = 0; $i < count($success); $i++) {
                    $response = $success[$i];
                    echo $this->getColoredString($response[0], 'green', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
                }
            }
            if ($errors = Schema::$ERRORS) {
                for ($i = 0; $i < count($errors); $i++) {
                    $response = $errors[$i];
                    echo $this->getColoredString($response[0], 'red', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
                }
            }
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}