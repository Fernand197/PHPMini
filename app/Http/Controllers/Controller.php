<?php

namespace App\Http\Controllers;

use Database\DBConnection;

abstract class Controller
{
    /**
     * @var DBConnection
     */
    protected DBConnection $db;
    
    
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = new DBConnection(
            env("DB_CONNECTION", "mysql"),
            env("DB_DATABASE"),
            env("DB_HOST"),
            env("DB_USERNAME"),
            env("DB_PASSWORD")
        );
    }
    
    /**
     * @return DBConnection
     */
    protected function getDB(): DBConnection
    {
        return $this->db;
    }
}
