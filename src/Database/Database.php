<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final class Database
{
    private static ?self $instance = null;

    private PDO $connection;

    private function __construct()
    {
        require_once __DIR__ . '/../../config/database.php';

        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->connection = $GLOBALS['pdo'];
            return;
        }

        $connection = new PDO(
            sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            ),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $GLOBALS['pdo'] = $connection;
        $this->connection = $connection;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new RuntimeException('No se puede deserializar la conexión de base de datos.');
    }
}
