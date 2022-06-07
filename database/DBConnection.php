<?php

namespace Database;

use PDO;
use Exception;

class DBConnection
{

    private $dbname;
    private $host;
    private $username;
    private $password;
    private $pdo;
    private $db;
    private $dsn;


    public function __construct(string $db = "mysql", string $dbname, string $host, string $username, string $password)
    {
        $this->db = $db;
        $this->dbname = $dbname;
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->dsn = "{$this->db}:dbname={$this->dbname};host={$this->host}";
    }


    // get instance of pdo
    public function getPDO(): PDO
    {

        try {
            return $this->pdo ?? $this->pdo = new PDO($this->dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]);
        } catch (Exception $e) {
            echo "Can't connect to database ";
        }
    }
}
