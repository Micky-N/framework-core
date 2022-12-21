<?php

namespace MkyCore;

use Exception;
use PDO;
use PDOException;

class Database
{

    private ?PDO $connection;

    /**
     * Create and get PDO connection
     *
     * @throws Exception
     */
    public function __construct(private readonly array $config)
    {
        $pdo = match ($this->config['system']) {
            'sqlite' => $this->setSqlitePDO($this->config),
            default => $this->setMysqlPDO($this->config)
        };
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("SET NAMES utf8");
        $this->connection = $pdo;
    }

    private function setSqlitePDO(array $config): PDO
    {
        return new PDO('sqlite:' . $config['file']);
    }

    private function setMysqlPDO(array $config): PDO
    {
        return new PDO("mysql:host={$config['host']};dbname={$config['name']}", $config['user'], $config['password']);
    }

    /**
     * Run query request
     *
     * @param $statement
     * @param null $class_name
     * @param bool $one
     * @return array|mixed
     * @throws Exception
     */
    public function query($statement, $class_name = null, bool $one = false): mixed
    {
        $req = $this->connection->query($statement);
        $req->setFetchMode(PDO::FETCH_ASSOC);
        $res = $one ? $req->fetch() : $req->fetchAll();
        if ($class_name && $res) {
            return $one ? new $class_name($res ?: []) : array_map(fn($fetch) => new $class_name($fetch), $res ?: []);
        }
        return $res;
    }

    public function getDatabase(): string
    {
        return $this->config['name'];
    }

    /**
     * Run prepare request
     *
     * @param $statement
     * @param $attribute
     * @param null $class_name
     * @param bool $one
     * @return array|bool|mixed
     * @throws Exception
     */
    public function prepare($statement, $attribute, $class_name = null, bool $one = false): mixed
    {
        $getPdoParams = $this->getPdoParams($attribute);
        $req = $this->connection->prepare($statement);
        foreach ($attribute as $key => $value) {
            $req->bindValue(":$key", $value, $getPdoParams[$key]);
        }
        $res = $req->execute();
        if (
            str_starts_with($statement, 'UPDATE') ||
            str_starts_with($statement, 'INSERT') ||
            str_starts_with($statement, 'DELETE')
        ) {
            return $res;
        }
        $req->setFetchMode(PDO::FETCH_ASSOC);
        $resFetched = $one ? $req->fetch() : $req->fetchAll();
        if ($class_name && $resFetched) {
            return $one ? new $class_name($resFetched) : array_map(fn($fetch) => new $class_name($fetch), $resFetched);
        }
        return $resFetched;
    }

    private function getPdoParams(array $arrayEntity): array
    {
        $res = [];
        foreach ($arrayEntity as $key => $value) {
            if (is_float($value)) {
                $res[$key] = PDO::PARAM_STR;
            } else if (is_integer($value)) {
                $res[$key] = PDO::PARAM_INT;
            } else if (is_bool($value)) {
                $res[$key] = PDO::PARAM_BOOL;
            } else {
                $res[$key] = PDO::PARAM_STR;
            }
        }
        return $res;
    }

    /**
     * @return PDO|null
     */
    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

    /**
     * @param mixed $config
     * @param string $connection
     * @return PDO
     * @throws PDOException
     */
    private function getPdo(mixed $config, string $connection): PDO
    {
        $startDsn = "$connection:";
        switch ($connection) {
            case 'mysql':
                $dsn = $startDsn . 'dbname=' . $config['name'] . ';host=' . $config['host'];
                $pdo = new PDO($dsn, $config['user'], $config['password']);
                break;
            case 'sqlite':
                $dsn = $startDsn . $config['filename'];
                $pdo = new PDO($dsn, $config['user'], $config['password']);
                break;
            default:
                throw new PDOException('Unknown connection server');
        }
        return $pdo;
    }
}