<?php
declare(strict_types=1);
namespace BizStock\Repositories;
use BizStock\Http\HttpException;
use BizStock\Support\CanonicalJson;

final readonly class OperationRepository
{
    public function __construct(private \PDO $pdo){}
    public function reserve(int $actor,string $kind,string $key,string $hash): ?array
    {
        $query=$this->pdo->prepare('SELECT * FROM operation_requests WHERE actor_id=? AND request_key=? FOR UPDATE');$query->execute([$actor,$key]);$row=$query->fetch();
        if($row){
            if($row['operation_kind']!==$kind || !hash_equals($row['payload_hash'],$hash)){throw new HttpException(409,'IDEMPOTENCY_CONFLICT','This operation key was used for different data.');}
            if($row['response_json']===null){throw new HttpException(409,'OPERATION_INCOMPLETE','Operation requires review.');}
            return json_decode($row['response_json'],true,32,JSON_THROW_ON_ERROR);
        }
        $query=$this->pdo->prepare('INSERT INTO operation_requests(actor_id,operation_kind,request_key,payload_hash) VALUES (?,?,?,?)');$query->execute([$actor,$kind,$key,$hash]);return null;
    }
    public function complete(int $actor,string $key,int $product,?int $movement,array $result): array
    {
        $query=$this->pdo->prepare('UPDATE operation_requests SET product_id=?,transaction_id=?,response_status=201,response_json=? WHERE actor_id=? AND request_key=?');
        $encoded=CanonicalJson::encode($result);$query->execute([$product,$movement,$encoded,$actor,$key]);return json_decode($encoded,true,32,JSON_THROW_ON_ERROR);
    }
}
