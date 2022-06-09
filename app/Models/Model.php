<?php

namespace App\Models;

use Closure;
use Database\DBConnection;
use Exception;
use PDO;

class Model
{

    protected static $db;
    protected static $table;
    protected static $primaryKey = "id";
    protected static $sql;
    protected static $params = [];

    public function __construct()
    {
        static::$db = new DBConnection(DB, DB_NAME, DB_HOST, DB_USER, DB_PWD);
    }

    public static function getInstance()
    {
        return new static();
    }

    // select all data tables
    public static function all(array $columns = ["*"]): array
    {
        $columns = implode(',', $columns);
        static::$sql =  "SELECT $columns FROM " . static::$table;
        return static::query();
    }

    public static function lastInsert()
    {
        $id = static::$db->getPDO()->lastInsertId();
        return static::findOrFail($id);
    }

    // select a row of table using id field
    public static function find($id, array $columns = ['*'])
    {
        $columns = implode(",", $columns);
        static::$sql = "SELECT $columns FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        static::$params = [$id];
        return static::query(true);
    }

    public static function findOrFail(int $id, array $columns = ['*'])
    {
        $result = static::find($id, $columns);
        if (is_null($result)) {
            throw new Exception("Model " . static::class . " not fount");
        }
        return $result;
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
        static::$sql = "INSERT INTO " . static::$table . " ($fields) VALUES ($values)";
        static::$params = $data;
        static::query(true);
        return static::lastInsert();
    }

    public function first(array $columns = ['*'])
    {
        return static::query(true);
    }

    public function firstOrFail(array $columns = ['*'])
    {
        $result = $this->first($columns);
        if (empty($result)) {
            throw new Exception("Model " . static::class . " not fount");
        }
        return $result;
    }

    public function get(array $columns = ['*']): array
    {
        return static::query();
    }

    public function oderBy(string $column, string $dir = "asc")
    {
        static::$sql .= " ORDER BY $column $dir";
        return static::query();
    }

    public static function firstOrCreate(array $attributes = [], array $values = [])
    {
        $result = static::where($attributes)->first();
        if (is_null($result)) {
            $result = static::create(array_merge($attributes, $values));
        }
        return $result;
    }

    public static function updateOrCreate(array $attributes = [], array $values = [])
    {
        $result = static::where($attributes)->first();
        if ($result) {
            $result = $result->update($values);
        } else {
            $result = static::create(array_merge($attributes, $values));
        }
        return $result;
    }

    public function firstOr($columns = ['*'], Closure $callback = null)
    {
        $result = $this->first();
        if ($result) {
            return $result;
        }
        if (is_callable($columns)) {
            return call_user_func_array($columns, []);
        }
        return call_user_func_array($callback, []);
    }

    public static function destroy($ids): bool
    {
        if (is_array($ids)) {
            $ids = implode(",", $ids);
            static::$sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " IN ($ids)";
            return static::query();
        }
        static::$sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        static::$params = [$ids];
        return static::query();
    }

    public function update(array $data): bool
    {
        $sqlRequestPart = "";

        $i = 1;
        foreach ($data as $key => $value) {
            $comma = $i === count($data) ? "" : ", ";
            $sqlRequestPart .= "{$key} = :{$key}{$comma}";
            $i++;
        }
        $id = static::$primaryKey;
        $data[$id] = $this->$id;

        static::$sql = "UPDATE " . static::$table . " SET {$sqlRequestPart} WHERE " . static::$primaryKey . " = :" . static::$primaryKey;
        static::$params = $data;
        return static::query(true);
    }

    public function delete(): bool
    {
        static::$sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        $id = static::$primaryKey;
        static::$params = [$this->$id];
        static::query();
        return true;
    }


    public static function where($column, $operator = null, $value = null)
    {
        $wheres = "";

        if (is_array($column)) {
            $i = 1;
            foreach ($column as $key => $value) {
                $comma = $i === count($column) ? "" : " AND ";
                $wheres .= "{$key} = :{$key}{$comma}";
                $i++;
            }
        } else {
            $wheres = "$column $operator ?";
        }

        $model = static::getInstance();

        $model::$sql = "SELECT * FROM " . static::$table . " WHERE {$wheres}";
        $model::$params = is_array($column) ? $column : [$value];
        // var_dump($model::$sql);
        return $model;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        $wheres = "";

        if (is_array($column)) {
            $i = 1;
            foreach ($column as $key => $value) {
                $comma = $i === count($column) ? "" : " OR ";
                $wheres .= "{$key} = :{$key}{$comma}";
                $i++;
            }
        } else {
            $wheres .= " OR $column $operator $value";
        }

        static::$sql .= "{$wheres}";
        return $this;
    }

    /**
     * Set query for do something in database
     * 
     * @param bool $single For retrieve one instance
     */
    private static function query(bool $single = null)
    {
        $params = static::$params;
        static::$db = static::$db ?? new DBConnection(DB, DB_NAME, DB_HOST, DB_USER, DB_PWD);
        $method = is_null($params) ? 'query' : 'prepare';
        $fetch = is_null($single) ? 'fetchAll' : 'fetch';

        if (strpos(static::$sql, 'INSERT') === 0 || strpos(static::$sql, 'UPDATE') === 0 || strpos(static::$sql, 'DELETE') === 0) {
            $stmt = static::$db->getPDO()->$method(static::$sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS, static::class, [static::$db]);
            return $stmt->execute($params);
        }

        $stmt = static::$db->getPDO()->$method(static::$sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class, [static::$db]);

        if ($method === 'query') {
            $results = $stmt->$fetch();
        } else {
            $stmt->execute($params);
            $results = $stmt->$fetch();
        }
        return $results;
    }
}
