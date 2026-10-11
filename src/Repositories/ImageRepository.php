<?php
declare(strict_types=1);
namespace BizStock\Repositories;
final readonly class ImageRepository
{
    public function __construct(private \PDO $pdo){}
    private function target(bool $product): array {return $product?['products','product_id','product_image']:['users','user_id','profile_image'];}
    public function find(bool $product,int $id,bool $lock=false): ?array {
        [$table,$key,$column]=$this->target($product);
        $q=$this->pdo->prepare("SELECT {$column} AS filename,is_active FROM {$table} WHERE {$key}=?".($lock?' FOR UPDATE':''));$q->execute([$id]);return $q->fetch()?:null;
    }
    public function replace(bool $product,int $id,?string $filename): void {
        [$table,$key,$column]=$this->target($product);
        $q=$this->pdo->prepare("UPDATE {$table} SET {$column}=? WHERE {$key}=?");$q->execute([$filename,$id]);
    }
}
