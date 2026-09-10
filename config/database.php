<?php

define('DB_HOST', getenv('THRIFTVIBE_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('THRIFTVIBE_DB_NAME') ?: 'thriftvibe');
define('DB_USER', getenv('THRIFTVIBE_DB_USER') ?: 'root');
define('DB_PASS', getenv('THRIFTVIBE_DB_PASSWORD') ?: '');

/**
 * Return a shared PDO database connection.
 */
function getDB()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            die('Database connection failed. Check your local configuration.');
        }
    }

    return $pdo;
}
