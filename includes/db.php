<?php
// ============================================================
//  STEADVOLT — Database Connection (PDO singleton)
//  File: includes/db.php
// ============================================================
require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    die('DB Connection failed: ' . $e->getMessage());
                }
                die('Service temporarily unavailable. Please try again later.');
            }
        }
        return self::$pdo;
    }

    /** Execute a query and return the PDOStatement */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch single row */
    public static function row(string $sql, array $params = []): ?array {
        $r = self::query($sql, $params)->fetch();
        return $r ?: null;
    }

    /** Fetch all rows */
    public static function all(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /** Fetch single value */
    public static function val(string $sql, array $params = []): mixed {
        $r = self::query($sql, $params)->fetchColumn();
        return $r === false ? null : $r;
    }

    /** Insert and return last insert ID */
    public static function insert(string $sql, array $params = []): int {
        self::query($sql, $params);
        return (int) self::conn()->lastInsertId();
    }

    /** Get a setting value */
    public static function setting(string $key, mixed $default = ''): string {
        $v = self::val("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $v !== null ? $v : $default;
    }

    /** Update a setting */
    public static function setSetting(string $key, string $value): void {
        self::query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)",
            [$key, $value]
        );
    }
}
