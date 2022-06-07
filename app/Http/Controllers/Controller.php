<?php

namespace App\Http\Controllers;

use Database\DBConnection;

abstract class Controller
{
    protected $db;


    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = new DBConnection(DB, DB_NAME, DB_HOST, DB_USER, DB_PWD);
    }
    // render a view 
    protected function view(string $path, array $context = [], ?string $layout = null)
    {
        if (!empty($context)) {
            foreach ($context as $k => $value) {
                $$k = $value;
            }
        }

        if (is_null($layout)) {
            $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
            require VIEWS . $path . '.php';
        } else {
            ob_start();
            $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
            require VIEWS . $path . '.php';
            $content = ob_get_clean();
            $layout_path = str_replace('.', DIRECTORY_SEPARATOR, $layout);
            require VIEWS . $layout_path . '.php';
        }
    }

    protected function getDB(): DBConnection
    {
        return $this->db;
    }
}
