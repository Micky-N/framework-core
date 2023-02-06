<?php

namespace MkyCore\Migration;

use Exception;
use MkyCore\Database;

class DB
{
    private const TABLE = 'migration_logs';

    public function __construct(private readonly Database $database)
    {
        try {
            $this->createTable();
        } catch (Exception $e) {
        }
    }

    /**
     * @throws Exception
     */
    private function createTable(): void
    {
        $table = self::TABLE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
`id` INT AUTO_INCREMENT NOT NULL,
`log` varchar(100) NOT NULL,
PRIMARY KEY (`id`))";

        $this->database->query($sql);
    }

    public static function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @param string $migration
     * @return bool
     */
    public function addLog(string $migration): bool
    {
        $table = self::TABLE;
        $migration = str_replace('.php', '', $migration);
        try {
            $this->database->prepare("INSERT INTO $table (log) VALUES(:log)", ['log' => $migration]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getLast(int|string $limit = 1): array
    {
        $table = self::TABLE;
        try {
            $limit = 'all' ? '' : "LIMIT $limit";
            return $this->database->query("SELECT * FROM $table ORDER BY id DESC $limit");
        } catch (Exception $e) {
            return [];
        }
    }

    public function deleteLog(string $migration): bool
    {
        $table = self::TABLE;
        $migration = str_replace('.php', '', $migration);
        try {
            $this->database->prepare("DELETE FROM $table WHERE log = :log", ['log' => $migration]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getTo(int $version): array
    {
        $migrationLogs = $this->getAll();
        if ($migrationLogs) {
            return array_filter($migrationLogs, function ($migrationFile) use ($version) {
                $migrationVersion = preg_replace('/[._a-z]+/', '', $migrationFile);
                return (int)$migrationVersion >= $version;
            });
        }
        return [];
    }

    public function getAll(): array
    {
        $table = self::TABLE;
        try {
            return $this->database->query("SELECT * FROM $table ORDER BY id DESC");
        } catch (Exception $e) {
            return [];
        }
    }

    public function isLogExists(string $log): bool
    {
        $table = self::TABLE;
        try {
            $res = $this->database->prepare("SELECT * FROM $table WHERE log = :log", ['log' => $log]);
            return $res ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }
}