<?php
declare(strict_types=1);
namespace BizStock\Support;
final class ReadSnapshot
{
 public static function run(\PDO $pdo,callable $read): array
 {
  if($pdo->inTransaction()){throw new \LogicException('Read snapshot owns its transaction.');}
  $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
  try{$result=$read();$pdo->commit();return $result;}catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 }
 public static function timestamp(\PDO $pdo): string
 {return (new \DateTimeImmutable($pdo->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn(),new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');}
}
