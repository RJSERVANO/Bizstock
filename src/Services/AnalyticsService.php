<?php
declare(strict_types=1);namespace BizStock\Services;
use BizStock\Support\{ApiInput,ReadSnapshot};use BizStock\Repositories\AnalyticsRepository;use BizStock\Http\{Authorization,HttpException};
final readonly class AnalyticsService
{
 public function __construct(private \PDO $pdo){}
 public static function range(array $query):array{
  if(isset($query['period']))ApiInput::choice($query['period'],'period',['7','30']);
  if(isset($query['date_from'])||isset($query['date_to'])){if(!isset($query['date_from'],$query['date_to']))throw new HttpException(422,'VALIDATION_ERROR','Supply both Manila dates.');$dates=ApiInput::dateRange($query);}
  else{$period=ApiInput::choice($query['period']??'7','period',['7','30']);$today=new \DateTimeImmutable('today',new \DateTimeZone('Asia/Manila'));$dates=['date_from'=>$today->modify('-'.((int)$period-1).' days')->format('Y-m-d'),'date_to'=>$today->format('Y-m-d')];}
  $from=new \DateTimeImmutable($dates['date_from'],new \DateTimeZone('Asia/Manila'));$until=(new \DateTimeImmutable($dates['date_to'],new \DateTimeZone('Asia/Manila')))->modify('+1 day');return [$dates,$from->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$until->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
 }
 public function read(array $query,string $role):array{
  Authorization::requireRole(['role'=>$role],['Admin','Staff']);[$dates,$from,$until]=self::range($query);$group=ApiInput::choice($query['group']??'top','group',['top','product','category','inactive','unit']);$page=ApiInput::page($query,['group_id'=>'group_id'],'group_id');return ReadSnapshot::run($this->pdo,function()use($group,$page,$from,$until,$dates){$repo=new AnalyticsRepository($this->pdo);$r=$group==='inactive'?$repo->inactive($from,$until,$page['per_page'],$page['offset']):$repo->movementSummary($from,$until,$group,$page['per_page'],$page['offset']);return ['data'=>['items'=>$r['data'],'snapshot_at'=>ReadSnapshot::timestamp($this->pdo),'filters'=>$dates+['group'=>$group],'timezone'=>'Asia/Manila','unclassified_events'=>$repo->unclassified($from,$until),'definition'=>'Operational movements exclude openings and count adjustments. Reversals offset the original direction at reversal time. Categories use current assignment. Quantities are grouped by captured unit, including unspecified. Ambiguous legacy receipts are excluded and counted separately; products with ambiguous period activity are not labeled inactive.'],'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$r['total'],'total_pages'=>(int)ceil($r['total']/$page['per_page'])]];});
 }
}
