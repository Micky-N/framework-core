<?php

namespace MkyCore\Migration;

use Exception;
use MkyCore\Console\Color;
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
     * @throws Exception
     */
    public static function create(string $table, $callback): bool
    {
        $callbackValues = $callback(new MethodType($table));
        return self::createQuery($table, $callbackValues);
    }

    /**
     * @throws Exception
     */
    private static function createQuery(string $table, array $values): bool
    {
        $queryCreate = "CREATE TABLE `$table`\n";
        $queryCreate .= "(\n    ";
        $queryCreate .= implode(",\n    ", $values);
        $queryCreate .= "\n);\n\n";
        return self::runQuery($table, $queryCreate);
    }

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
        try {
            if (self::migrationTable($query)) {
                self::$SUCCESS[] = ['database table ' . ($creating ? 'created' : 'updated'), $table];
                return true;
            }
            self::$ERRORS[] = ['error in ' . ($creating ? 'creation' : 'alteration') . ' table in database', $table];
            return false;
        } catch (Exception $ex) {
            self::$ERRORS[] = ['error in ' . ($creating ? 'creation' : 'alteration') . ' table in database', $ex->getMessage()];
            return false;
        }
    }

    /**
     * @param string $query
     * @return bool
     * @throws PDOException
     */
    private static function migrationTable(string $query): bool
    {
        try {
            return DB::prepare($query, []) !== false;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function alter(string $table, $callback): bool
    {
        $callbackValues = $callback(new MethodType($table));
        return self::alterQuery($table, $callbackValues);
    }

    /**
     * @throws Exception
     */
    private static function alterQuery(string $table, array $values): bool
    {
        $queryCreate = "ALTER TABLE `$table`\n";
        $queryCreate .= implode(",\n", array_map(function ($value) {
            $test = explode(' ', $value);
            $test = $test[0];
            if (!in_array($test, self::ALTERS)) {
                return "ADD $value";
            }
            return $value;
        }, $values));
        $queryCreate .= ";\n\n";
        return self::runQuery($table, $queryCreate);
    }

    /**
     * @param string $table
     * @return bool|string
     */
    public static function drop(string $table): bool
    {
        return self::dropTableIfExists($table);
    }

    private static function dropTableIfExists(string $table): bool
    {
        $queryCreate = "DROP TABLE IF EXISTS `$table`\n\n";
        if (Run::$query) {
            echo $queryCreate;
            return true;
        }
        if (self::migrationTable($queryCreate)) {
            self::$SUCCESS[] = ['database table dropped', $table];
            return true;
        }
        self::$ERRORS[] = ['error while dropping table from database', $table];
        return false;
    }
}