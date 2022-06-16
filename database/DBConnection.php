<?php

namespace Database;

use PDO;
use Exception;
use PDOException;

class DBConnection
{

    private $username;
    private $password;
    private $pdo;
    private $dsn;


    public function __construct(string $db, string $dbname, string $host, string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->dsn = "{$db}:dbname={$dbname};host={$host}";
    }


    // get instance of pdo
    public function getPDO(): PDO
    {

        try {
            return $this->pdo ?? $this->pdo = new PDO($this->dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }
    }
}
