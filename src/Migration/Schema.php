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

    /**
     * @throws Exception
     */
    public static function create(string $table, $callback): bool|string
    {
        $callbackValues = $callback(new MethodType($table));
        return self::createQuery($table, $callbackValues);
    }

    /**
     * @param string $table
     * @return bool|string
     */
    public static function drop(string $table): bool|string
    {
        return self::dropTableIfExists($table);
    }

    /**
     * @throws Exception
     */
    public static function alter(string $table, $callback): bool|string
    {
        $callbackValues = $callback(new MethodType($table));
        return self::createQuery($table, $callbackValues);
    }

    /**
     * @throws Exception
     */
    private static function createQuery(string $table, array $values): bool|string
    {
        $queryCreate = "CREATE TABLE `$table`\n";
        $queryCreate .= "(\n    ";
        $queryCreate .= implode(",\n    ", $values);
        $queryCreate .= "\n)";
        if (Run::$query) {
            echo $queryCreate;
            return true;
        }
        if ($success = self::migrationTable($queryCreate)) {
            return self::getInstance()->sendSuccess('database table created', $table);
        }
        return self::getInstance()->sendError('error creating table in database', $table);
    }

    /**
     * @param string $tableCreated
     * @return bool
     * @throws PDOException
     */
    private static function migrationTable(string $tableCreated): bool
    {
        try {
            $stmt = DB::getConnection()->prepare($tableCreated);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    private static function dropTableIfExists(string $table): bool
    {
        $queryCreate = "DROP TABLE IF EXISTS `$table`";
        if (Run::$query) {
            echo $queryCreate;
            return true;
        }
        if (self::migrationTable($queryCreate)) {
            return self::getInstance()->sendSuccess('database table dropped', $table);
        }
        return self::getInstance()->sendError('error while dropping table from database', $table);
    }
}