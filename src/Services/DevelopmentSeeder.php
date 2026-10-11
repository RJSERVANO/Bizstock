<?php
declare(strict_types=1);
namespace BizStock\Services;

use BizStock\Support\Config;
use BizStock\Support\CanonicalJson;
use BizStock\Support\PasswordPolicy;
use PDO;
use RuntimeException;

final class DevelopmentSeeder
{
    public function __construct(private readonly PDO $pdo,private readonly Config $config) {}
    /** Only a pristine local/test schema. Never upsert, replace, truncate or merge with real data. */
    public function run(?callable $deliverCredentials=null): array
    {
        if (!in_array($this->config->environment,['local','test'],true) || !in_array($this->config->dbHost,['127.0.0.1','localhost','::1'],true)) { throw new RuntimeException('Development seeds require local/test and loopback.'); }
        if($this->pdo->inTransaction()){throw new RuntimeException('Seeder owns its transaction.');}
        $credentials=[];
        foreach(['Admin','Staff','Viewer'] as $role){$credentials[$role]=['username'=>'dev_'.strtolower($role),'password'=>bin2hex(random_bytes(16))];}
        $hashes=[]; foreach($credentials as $role=>$credential){$hashes[$role]=PasswordPolicy::hash($credential['password']);}
        $this->pdo->beginTransaction();
        try {
            if((int)$this->pdo->query('SELECT lock_id FROM security_locks WHERE lock_id=1 FOR UPDATE')->fetchColumn()!==1){throw new RuntimeException('Security sentinel missing.');}
            $done=$this->pdo->query("SELECT COUNT(*) FROM audit_events WHERE action='development.seed'")->fetchColumn();
            if((int)$done>0){$this->pdo->rollBack();return ['status'=>'already seeded','credentials'=>[]];}
            foreach(['users','categories','suppliers','products','transactions','operation_requests','audit_events','rate_limits'] as $table){
                if((int)$this->pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn()>0){throw new RuntimeException('Seeds require empty domain/support data; existing data preserved.');}
            }
            $users=[];
            $insert=$this->pdo->prepare('INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,?,0)');
            foreach($credentials as $role=>$credential){$insert->execute(['Development '.$role,$credential['username'],$hashes[$role],$role]);$users[$role]=(int)$this->pdo->lastInsertId();}
            $seedActor=(new \BizStock\Repositories\AuthRepository($this->pdo))->byId($users['Admin'],true);
            $stock=new StockMovementService($this->pdo);
            $this->pdo->exec("INSERT INTO categories(category_name) VALUES ('Office supplies')"); $category=(int)$this->pdo->lastInsertId();
            $this->pdo->exec("INSERT INTO suppliers(supplier_name,contact_email) VALUES ('Development supplier','supplier@example.invalid')");$supplier=(int)$this->pdo->lastInsertId();
            $products=require $this->config->root.'/database/seeds/development.php';
            foreach($products as $product){
                $insert=$this->pdo->prepare('INSERT INTO products(sku,product_name,category_id,supplier_id,price,minimum_threshold) VALUES (?,?,?,?,?,?)');
                $insert->execute([$product['sku'],$product['name'],$category,$supplier,$product['price'],$product['threshold']]);$id=(int)$this->pdo->lastInsertId();
                // Shared stock writer; account forced-change flags are set before this transaction is visible.
                foreach(['IN'=>$product['opening'],'OUT'=>$product['out']] as $type=>$quantity){
                    if($quantity===0){continue;}
                    $key='dev-seed:'.$product['sku'].':'.$type;
                    $stock->recordInTransaction(['product_id'=>$id,'type'=>$type,'quantity'=>$quantity,'notes'=>'Development seed'.($type==='IN'?' opening stock':' stock example')],$seedActor,$key);
                }
            }
            $this->pdo->exec('UPDATE users SET must_change_password=1');
            $insert=$this->pdo->prepare("INSERT INTO audit_events(actor_id,action,entity_type,after_json,request_id) VALUES (?,'development.seed','system',?,?)");
            $insert->execute([$users['Admin'],CanonicalJson::encode(['fixture_version'=>1,'products'=>count($products)]),'cli-'.bin2hex(random_bytes(16))]);
            if($deliverCredentials!==null){$deliverCredentials($credentials);}
            $this->pdo->commit();return ['status'=>'seeded','credentials'=>$credentials];
        }catch(\Throwable $error){if($this->pdo->inTransaction()){$this->pdo->rollBack();}throw $error;}
    }
}
