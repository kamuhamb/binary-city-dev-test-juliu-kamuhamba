<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/** Single shared PDO connection (PostgreSQL). */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = (require __DIR__ . '/../../config/config.php')['db'];
            $dsn = "pgsql:host={$c['host']};port={$c['port']};dbname={$c['name']}";
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
            ]);
        }
        return self::$pdo;
    }

    /** Postgres unique_violation is SQLSTATE 23505. */
    public static function isUniqueViolation(\PDOException $e): bool
    {
        return $e->getCode() === '23505';
    }
}
