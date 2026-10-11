<?php
declare(strict_types=1);

namespace BizStock\Support;

use PDO;
use PDOException;
use RuntimeException;

final readonly class DatabasePlatform
{
    public const TEXT_COLLATION = 'utf8mb4_unicode_ci';
    public const ID_COLLATION = 'ascii_bin';

    public function __construct(public string $engine, public string $version) {}

    public static function detect(string $version, string $comment, string $target, string $environment): self
    {
        if (stripos($version . ' ' . $comment, 'mariadb') !== false) {
            if ($target !== 'mariadb104-local' || !in_array($environment, ['local', 'test'], true)
                || !preg_match('/^(?:5\.5\.5-)?10\.4\.32-MariaDB(?:[-+].*)?$/iD', $version)) {
                throw new RuntimeException('MariaDB requires the explicit local 10.4.32 target.');
            }
            return new self('mariadb', '10.4.32');
        }
        if ($target !== 'mysql84' || !preg_match('/^8\.4\.\d+(?:[-+].*)?$/D', $version)
            || stripos($comment, 'mysql') === false) {
            throw new RuntimeException('Expected the explicitly selected MySQL 8.4 target.');
        }
        return new self('mysql', $version);
    }

    public function configure(PDO $pdo): void
    {
        $pdo->exec("SET time_zone = '+00:00'");
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ONLY_FULL_GROUP_BY,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        if ($this->engine === 'mariadb') {
            // Enforce checks even if a local server was started with them disabled.
            $pdo->exec('SET SESSION check_constraint_checks = ON');
        }
        $pdo->exec('SET SESSION foreign_key_checks = ON');
        $row = $pdo->query("SELECT @@session.time_zone AS tz, @@session.foreign_key_checks AS fk, @@session.collation_connection AS collation, @@session.sql_mode AS mode")->fetch();
        if ($row['tz'] !== '+00:00' || (int) $row['fk'] !== 1 || $row['collation'] !== self::TEXT_COLLATION
            || !str_contains($row['mode'], 'STRICT_TRANS_TABLES') || !str_contains($row['mode'], 'ONLY_FULL_GROUP_BY')
            || ($this->engine === 'mariadb' && (int) $pdo->query('SELECT @@session.check_constraint_checks')->fetchColumn() !== 1)) {
            throw new RuntimeException('Required database session settings were not applied.');
        }
        $engine = $pdo->query("SELECT SUPPORT FROM information_schema.ENGINES WHERE ENGINE = 'InnoDB'")->fetchColumn();
        if (!in_array($engine, ['YES', 'DEFAULT'], true)) {
            throw new RuntimeException('InnoDB is required.');
        }
    }

    // Portable JSON storage: explicit text plus named JSON_VALID constraint, no engine JSON equality.
    public static function jsonColumn(string $column, string $constraint): string
    {
        foreach ([$column, $constraint] as $identifier) {
            if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $identifier)) {
                throw new \InvalidArgumentException('Unsafe schema identifier.');
            }
        }
        return "`{$column}` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, CONSTRAINT `{$constraint}` CHECK (`{$column}` IS NULL OR JSON_VALID(`{$column}`))";
    }

    public static function failureCode(PDOException $error): string
    {
        // Internal classifier; HTTP error envelopes/authorization are unchanged.
        return match ((int) ($error->errorInfo[1] ?? 0)) {
            1062 => 'duplicate',
            1451, 1452 => 'reference',
            4025, 3819 => 'constraint',
            1213 => 'deadlock',
            1205 => 'lock_timeout',
            1264, 1406 => 'range',
            default => 'database',
        };
    }
}
