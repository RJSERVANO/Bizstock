<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Support\ReadSnapshot;use BizStock\Repositories\{DashboardRepository,HistoryRepository};use BizStock\Http\Authorization;
final readonly class DashboardService
{
 public function __construct(private \PDO $pdo){}
 public function read(string $role,int $period=7): array
 {
  Authorization::requireRole(['role'=>$role],['Admin','Staff','Viewer']);
  if(!in_array($period,[7,30],true))throw new \BizStock\Http\HttpException(422,'VALIDATION_ERROR','Choose a 7 or 30 day activity period.');
  return ReadSnapshot::run($this->pdo,function()use($role,$period){$repository=new DashboardRepository($this->pdo);$result=$repository->counts();$result['low_stock']=(new ProductService($this->pdo))->listing(['low_stock'=>'true','per_page'=>'100','sort'=>'quantity'],'Viewer')['data'];$result['snapshot_at']=ReadSnapshot::timestamp($this->pdo);
   if($role!=='Viewer'){
    $result['recent_movements']=(new HistoryRepository($this->pdo))->listing(['per_page'=>'10','sort'=>'transaction_date','direction'=>'desc'])['data'];
    $today=(new \DateTimeImmutable($result['snapshot_at']))->setTimezone(new \DateTimeZone('Asia/Manila'))->setTime(0,0);
    $start=$today->modify('-'.($period-1).' days');$end=$today->modify('+1 day');
    $raw=$repository->activity($start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
    $days=[];$byDate=array_column($raw['days'],null,'date');for($day=$start;$day<$end;$day=$day->modify('+1 day')){$date=$day->format('Y-m-d');$days[]=$byDate[$date]??['date'=>$date,'units_in'=>'0','units_out'=>'0'];}
    $result['activity']=['period_days'=>$period,'date_from'=>$start->format('Y-m-d'),'date_to'=>$today->format('Y-m-d'),'timezone'=>'Asia/Manila','days'=>$days,'totals'=>$raw['totals']];
   }return $result;
  });
 }
}
