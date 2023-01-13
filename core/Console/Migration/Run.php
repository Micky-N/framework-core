<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;

class Run extends Migration
{

    public function process(): bool|string
    {
        /** @var MigrationFile $migrationRunner */
        $migrationRunner = $this->app->get(MigrationFile::class);
        self::$query = in_array('--query', $this->params);
        $params = $this->parseParams();
        $pop = false;
        $version = null;
        if(!empty($params['-v'])){
            $version = $params['-v'];
        }elseif(in_array('--pop', $this->params)){
            $pop = true;
        }
        try {
            $migrationRunner->actionMigration('up', $version);
            if ($pop) {
                exec('php mky populator:run', $output);
                for ($i = 0; $i < count($output); $i++) {
                    echo $output[$i];
                }
            }
            $this->sendResponse(Schema::$SUCCESS, Schema::$ERRORS);
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}