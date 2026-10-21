<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function connection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                throw $e;
            }
        }
        return self::$instance;
    }
}