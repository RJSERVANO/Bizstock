<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Http\{Authorization,HttpException};use BizStock\Repositories\HistoryRepository;use BizStock\Support\ReadSnapshot;
final readonly class ReportService
{
 public const EXPORT_ROWS=10000;public const EXPORT_BYTES=16777216;
 public function __construct(private \PDO $pdo){}
 public function read(string $report,array $query,string $role,bool $export=false): array
 {
  Authorization::requireRole(['role'=>$role],['Admin','Staff']);if(!in_array($report,['inventory','transactions'],true))throw new \LogicException('Unknown report.');
  if($report==='inventory'&&isset($query['active'])&&$query['active']!=='active')throw new HttpException(422,'VALIDATION_ERROR','Inventory snapshots include current active products only.');
  return ReadSnapshot::run($this->pdo,function()use($report,$query,$role,$export){$result=$report==='inventory'?(new ProductService($this->pdo))->listing($query,$role,$export?self::EXPORT_ROWS:null):(new HistoryRepository($this->pdo))->listing($query,$export?self::EXPORT_ROWS:null);
   $data=['snapshot_at'=>ReadSnapshot::timestamp($this->pdo),'currency'=>'PHP','items'=>$result['data']];if($report==='transactions')$data['reference_value']=$result['reference_value'];return ['data'=>$data,'meta'=>$result['meta']];
  });
 }
 public static function csv(string $report,array $rows): string
 {
  $columns=$report==='inventory'?['product_id','sku','product_name','category_id','category_name','supplier_id','supplier_name','location','description','quantity','minimum_threshold','stock_status','price','is_active','version','created_at','updated_at']:['transaction_id','product_id','sku','product_name','user_id','actor_name','type','quantity','unit_price','transaction_date','correction_of_transaction_id','reversed_by_transaction_id','notes'];
  $text=['sku','product_name','category_name','supplier_name','location','description','stock_status','actor_name','type','notes'];$stream=fopen('php://temp/maxmemory:2097152','w+');
  try{fputcsv($stream,$columns,',','"','',"\r\n");foreach($rows as $row){$values=[];foreach($columns as $key){$value=$row[$key]??'';if(is_bool($value))$value=$value?'1':'0';$value=(string)$value;if(in_array($key,$text,true)&&preg_match('/^[\s\x{00a0}]*[=+\-@\t\r\n]/u',$value))$value="'".$value;$values[]=$value;}fputcsv($stream,$values,',','"','',"\r\n");if(ftell($stream)>self::EXPORT_BYTES)throw new HttpException(422,'EXPORT_TOO_LARGE','Narrow the export to at most 16 MiB.');}rewind($stream);return stream_get_contents($stream);}finally{fclose($stream);}
 }
}
