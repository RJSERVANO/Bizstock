<?php
declare(strict_types=1);

namespace BizStock\Support;

use PDO;

final class PdoFactory
{
    private ?PDO $connection = null;
    private ?DatabasePlatform $platform = null;

    public function __construct(private readonly Config $config) {}

    public function connection(): PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $this->config->dbHost, $this->config->dbPort, $this->config->dbName),
            $this->config->dbUser,
            $this->config->dbPassword,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_TIMEOUT => 5],
        );
        $server = $pdo->query('SELECT VERSION() AS version, @@version_comment AS comment')->fetch();
        $platform = DatabasePlatform::detect($server['version'], $server['comment'],
            $this->config->dbTarget, $this->config->environment);
        $platform->configure($pdo);
        $this->platform = $platform;
        return $this->connection = $pdo;
    }

    public function platform(): DatabasePlatform
    {
        $this->connection();
        return $this->platform;
    }
}
