<?php

namespace PHPMini\Models;

use Closure;
use Database\DBConnection;
use Exception;
use PDO;
use function env;

class Model
{

    protected static DBConnection $db;
    protected static $table;
    protected static  $primaryKey = "id";
    protected static string $sql;
    protected static array $params = [];

    public function __construct()
    {
        static::$db = new DBConnection(
            env('DB_CONNECTION', "mysql"),
            env("DB_DATABASE"),
            env("DB_HOST"),
            env("DB_USERNAME"),
            env("DB_PASSWORD")
        );
    }

    public static function parseColumn($columns): string
    {
        return implode(',', $columns);
    }


    // select all data tables
    public static function all(array $columns = ["*"]): ModelCollection
    {
        $columns = static::parseColumn($columns);
        static::$sql =  "SELECT $columns FROM " . static::$table;
        return static::query();
    }
    
    /**
     * @throws Exception
     */
    public static function lastInsert()
    {
        $id = static::$db->getPDO()->lastInsertId();
        return static::findOrFail($id);
    }

    // select a row of table using id field
    public static function find($id, array $columns = ['*'])
    {
        $columns = static::parseColumn($columns);
        static::$sql = "SELECT $columns FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        static::$params = [$id];
        return static::query(true);
    }
    
    /**
     * @throws Exception
     */
    public static function findOrFail(int $id, array $columns = ['*'])
    {
        // var_dump("hey") or die;
        $result = static::find($id, $columns);
        if (is_null($result)) {
            throw new Exception("Model " . static::class . " not fount");
        }
        return $result;
    }


    // insert an instance in database
    
    /**
     * @throws Exception
     */
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
        // var_dump(static::$params) or die;
        return static::query(true);
    }

    public function firstOrFail(array $columns = ['*'])
    {
        $result = $this->first($columns);
        if ($result === null) {
            throw new \RuntimeException("Model " . static::class . " not fount");
        }
        return $result;
    }

    public function get(array $columns = ['*']): ModelCollection
    {
        // var_dump(static::$params) or die;
        return static::query();
    }

    public function orderBy(string $column, string $dir = "asc")
    {
        static::$sql .= " ORDER BY $column $dir";
        return $this;
    }

    public function orderByDesc(string $column)
    {
        return $this->orderBy($column, "desc");
    }

    public function limit(int $limit)
    {
        static::$sql .= " LIMIT $limit";
        return $this;
    }

    public function offset(int $offset)
    {
        static::$sql .= " OFFSET $offset";
        return $this;
    }
    
    /**
     * @throws Exception
     */
    public static function firstOrCreate(array $attributes = [], array $values = [])
    {
        $result = static::where($attributes)->first();
        if (is_null($result)) {
            $result = static::create(array_merge($attributes, $values));
        }
        return $result;
    }
    
    /**
     * @throws Exception
     */
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
        if (!is_array($columns) && is_callable($columns)) {
            $result = $this->first(["*"]);
            var_dump($result) or die;
            if ($result) {
                return $result;
            }
        }
        if (is_callable($columns)) {
            return call_user_func_array($columns, []);
        }
        return call_user_func_array($callback, []);
    }

    public static function findOr($key, $columns = ['*'], Closure $callback = null)
    {
        if (!is_array($columns) && is_callable($columns)) {
            $columns = ['*'];
            $result = static::find($key, $columns);
            if ($result) {
                return $result;
            }
        }
        if (is_callable($columns)) {
            return call_user_func_array($columns, []);
        }
        return call_user_func_array($callback, []);
    }

    public static function truncate()
    {
        static::$sql = "TRUNCATE TABLE " . static::$table;
        return static::query();
    }

    public static function destroy($ids): bool
    {
        if (is_array($ids)) {
            $ids = static::parseColumn($ids);
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
        $data[$id] = static::$$id;

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

    public function andWhere($column, $operator = null, $value = null)
    {
        $wheres = " AND ";
        if (is_array($column)) {
            $i = 1;
            $wheres .= "(";
            foreach ($column as $key => $v) {
                $comma = $i === count($column) ? "" : " AND ";
                $wheres .= "{$key} = ?{$comma}";
                static::$params[] = $v;
                $i++;
            }
            $wheres .= ")";
            // $model::$params =  $column;
        } elseif (is_string($column) && is_null($value)) {
            $wheres .= " $column = ?";
            static::$params[] = $operator;
        } else {
            $wheres .= "$column $operator ?";
            static::$params[] = $value;
        }

        static::$sql .= $wheres;

        return $this;
    }

    public static function where($column, $operator = null, $value = null)
    {
        $wheres = "";
        $model = new static;
        $model::$params = [];

        if (is_array($column)) {
            $i = 1;
            $wheres .= "(";
            foreach ($column as $key => $v) {
                $comma = $i === count($column) ? "" : " AND ";
                $wheres .= "{$key} = ?{$comma}";
                $model::$params[] = $v;
                $i++;
            }
            $wheres .= ")";
        } elseif (is_string($column) && is_null($value)) {
            $wheres = " $column = ?";
            $model::$params[] = $operator;
        } else {
            $wheres = "$column $operator ?";
            $model::$params[] = $value;
        }


        $model::$sql = "SELECT * FROM " . $model::$table . " WHERE {$wheres}";
        return $model;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        $wheres = " OR ";

        if (is_array($column)) {
            $i = 1;
            $wheres .= "(";
            foreach ($column as $key => $v) {
                $comma = $i === count($column) ? "" : " OR ";
                $wheres .= "{$key} = ?{$comma}";
                static::$params[] = $v;
                $i++;
            }
            $wheres .= ")";
        } elseif (is_string($column) && is_null($value)) {
            $wheres .= "$column = ?";
            static::$params[] = $operator;
        } else {
            $wheres .= "$column $operator ?";
            static::$params[] =  $value;
        }

        static::$sql .= $wheres;
        return $this;
    }

    /**
     * Set query for do something in database
     * 
     * @param bool $single For retrieve one instance
     */
    protected static function query(bool $single = null)
    {
        $params = static::$params;
        static::$db = static::$db ?? new DBConnection(env('DB_CONNECTION', "mysql"),
                env("DB_DATABASE"),
                env("DB_HOST"),
                env("DB_USERNAME"),
                env("DB_PASSWORD")
            );
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
            $results = $fetch === 'fetchAll' ? new ModelCollection($stmt->$fetch()) : $stmt->$fetch();
        } else {
            $stmt->execute($params);
            $results = $fetch === 'fetchAll' ? new ModelCollection($stmt->$fetch()) : $stmt->$fetch();
        }
        return $results;
    }

    public function getKeyName(): string
    {
        return static::$primaryKey;
    }

    public function getKey()
    {
        $name = $this->getKeyName();
        return $this->$name;
    }
}
