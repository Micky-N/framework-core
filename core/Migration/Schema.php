<?php

namespace MkyCore\Migration;

use Exception;
use MkyCommand\Color;
use MkyCore\Console\Migration\Run;
use MkyCore\Facades\DB;
use PDOException;


class Schema
{

    use Color;

    const ALTERS = ['MODIFY', 'DROP', 'CHANGE'];

    public static array $SUCCESS = [];
    public static array $ERRORS = [];


    /**
     * Create table
     *
     * @param string $table
     * @param callable $callback
     * @return bool
     */
    public static function create(string $table, callable $callback): bool
    {
        $migrationTable = new MigrationTable($table);
        $callback($migrationTable);
        return self::createQuery($table, $migrationTable);
    }

    /**
     * Create query statement
     *
     * @param string $table
     * @param MigrationTable $migrationTable
     * @return bool
     */
    private static function createQuery(string $table, MigrationTable $migrationTable): bool
    {
        $values = array_map(function ($value) {
            return $value->getQuery();
        }, $migrationTable->getColumns());
        $queryCreate = "CREATE TABLE `$table`\n";
        $queryCreate .= "(\n    ";
        $queryCreate .= implode(",\n    ", $values);
        $queryCreate .= "\n);\n\n";
        return self::runQuery($table, $queryCreate);
    }

    /**
     * Run query to create or update
     *
     * @param string $table
     * @param string $query
     * @return bool
     */
    private static function runQuery(string $table, string $query): bool
    {
        $creating = str_contains($query, 'CREATE TABLE');
        if (Run::$query) {
            echo $creating ?
                "-- List the structure of the table `$table`\n"
                : "-- Alteration of the table `$table`\n";

            echo $query;
            return true;
        }
        if (self::migrationTable($query)) {
            self::$SUCCESS[] = ['Database table ' . ($creating ? 'created' : 'updated'), $table];
            return true;
        }
        return false;
    }

    /**
     * Run statement to database
     *
     * @param string $query
     * @return bool
     * @throws PDOException
     */
    private static function migrationTable(string $query): bool
    {
        $creating = str_contains($query, 'CREATE TABLE');
        try {
            return DB::prepare($query, []) !== false;
        } catch (PDOException $ex) {
            self::$ERRORS[] = ['error in ' . ($creating ? 'creation' : 'alteration') . ' table in database', $ex->getMessage()];
            return false;
        }
    }

    /**
     * Update table
     *
     * @param string $table
     * @param callable $callback
     * @return bool
     */
    public static function alter(string $table, callable $callback): bool
    {
        $migrationTable = new MigrationTable($table);
        $callback($migrationTable);
        return self::alterQuery($table, $migrationTable);
    }


    /**
     * Alter query statement
     *
     * @param string $table
     * @param MigrationTable $migrationTable
     * @return bool
     */
    private static function alterQuery(string $table, MigrationTable $migrationTable): bool
    {
        $queryCreate = "ALTER TABLE `$table`\n";
        $queryCreate .= implode(",\n", array_map(function ($value) {
            $value = $value->getQuery();
            $test = explode(' ', $value);
            $test = $test[0];
            if (!in_array($test, self::ALTERS)) {
                return "ADD $value";
            }
            return $value;
        }, $migrationTable->getColumns()));
        $queryCreate .= ";\n\n";
        return self::runQuery($table, $queryCreate);
    }

    /**
     * Drop table
     *
     * @param string $table
     * @return bool
     */
    public static function dropTable(string $table): bool
    {
        return self::dropTableIfExists($table);
    }

    /**
     * Drop if exists
     *
     * @param string $table
     * @return bool
     */
    private static function dropTableIfExists(string $table): bool
    {
        $queryCreate = "DROP TABLE IF EXISTS `$table`\n\n";
        if (Run::$query) {
            echo $queryCreate;
            return true;
        }
        $tableExists = self::tableExists($table);
        if (self::migrationTable($queryCreate)) {
            if ($tableExists) {
                self::$SUCCESS[] = ['database table dropped', $table];
            }
            return true;
        }
        self::$ERRORS[] = ['error while dropping table from database', $table];
        return false;
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    private static function tableExists(string $table): bool
    {

        try {
            $result = DB::query("SELECT 1 FROM $table LIMIT 1");
        } catch (Exception $e) {
            // We got an exception (table not found)
            return false;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== false;
    }
}