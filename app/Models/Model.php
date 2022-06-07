<?php

namespace App\Models;

use Database\DBConnection;
use PDO;

class Model
{

    protected static $db;
    static protected $table;
    protected static $sql;

    public function __construct()
    {
        self::$db = new DBConnection(DB, DB_NAME, DB_HOST, DB_USER, DB_PWD);
    }

    // select all data tables
    public static function all(): array
    {
        self::$sql =  "SELECT * FROM " . static::$table;
        return self::query();
    }

    public static function lastId()
    {
        return static::$db->getPDO()->lastInsertId();
    }

    // select a row of table using id field
    public static function find(int $id)
    {
        self::$sql = "SELECT * FROM " . static::$table . " WHERE id = ?";
        return self::query([$id], true);
    }

    // insert an instance in database
    public static function create(array $data)
    {
        $fields = "";
        $values = "";
        $i = 1;
        foreach ($data as $key => $value) {
            $comma = $i === count($data) ? "" : ", ";
            $fields .= "{$key}{$comma}"; // set fields we want to insert
            $values .= ":{$key}{$comma}"; // set their value
            $i++;
        }
        self::$sql = "INSERT INTO " . static::$table . " ($fields) VALUES ($values)";

        return self::query($data);
    }

    public function oderBy(string $column, string $dir = "asc")
    {
        self::$sql .= " ORDER BY $column $dir";
        return self::query();
    }

    public static function __callStatic(string $name, array $parameters)
    {
        $name = "static_" . $name;
        return call_user_func_array($name, $parameters);
    }

    public function update($data)
    {
        $sqlRequestPart = "";

        $i = 1;
        foreach ($data as $key => $value) {
            $comma = $i === count($data) ? "" : ", ";
            $sqlRequestPart .= "{$key} = :{$key}{$comma}";
            $i++;
        }
        $data['id'] = $this->id;

        self::$sql = "UPDATE " . static::$table . " SET {$sqlRequestPart} WHERE id = :id";
        return self::query($data, true);
    }

    // update row in database
    public static function static_update(int $id, array $data)
    {
        // create a part of sql for fields that we want to update
        $sqlRequestPart = "";

        $i = 1;
        foreach ($data as $key => $value) {
            $comma = $i === count($data) ? "" : ", ";
            $sqlRequestPart .= "{$key} = :{$key}{$comma}";
            $i++;
        }
        $data['id'] = $id;

        self::$sql = "UPDATE " . static::$table . " SET {$sqlRequestPart} WHERE id = :id";
        return self::query($data, true);
    }

    public function delete()
    {
        self::$sql = "DELETE FROM " . static::$table . " WHERE id = ?";
        return self::query([$this->id]);
    }
    // delete a row in database using id
    public static function static_delete(int $id): bool
    {
        self::$sql = "DELETE FROM " . static::$table . " WHERE id = ?";
        return self::query([$id]);
    }

    public static function where(array $params)
    {
        $wheres = "";

        $i = 1;
        foreach ($params as $key => $value) {
            $comma = $i === count($params) ? "" : " AND ";
            $wheres .= "{$key} = :{$key}{$comma}";
            $i++;
        }

        self::$sql = "SELECT * FROM " . static::$table . " WHERE {$wheres}";
        return self::query($params);
    }

    /**
     * Set query for do something in database
     * 
     * @param array|null $params If we want to make prepared request
     * @param bool $single For retrieve one instance
     */
    private static function query(?array $params = null, bool $single = null)
    {
        $method = is_null($params) ? 'query' : 'prepare';
        $fetch = is_null($single) ? 'fetchAll' : 'fetch';
        self::$db = new DBConnection(DB, DB_NAME, DB_HOST, DB_USER, DB_PWD);

        if (strpos(self::$sql, 'INSERT') === 0 || strpos(self::$sql, 'UPDATE') === 0 || strpos(self::$sql, 'DELETE') === 0) {
            $stmt = self::$db->getPDO()->$method(self::$sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS, self::class, [self::$db]);
            return $stmt->execute($params);
        }

        $stmt = self::$db->getPDO()->$method(self::$sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class, [self::$db]);

        if ($method === 'query') {
            return $stmt->$fetch();
        } else {
            $stmt->execute($params);
            return $stmt->$fetch();
        }
    }
}
