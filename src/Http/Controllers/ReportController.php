<?php
declare(strict_types=1);namespace BizStock\Http\Controllers;
use BizStock\Support\{PdoFactory,ApiInput};use BizStock\Services\{AuthService,ReportService};use BizStock\Http\{Authorization,Response};
final readonly class ReportController
{
 public function __construct(private PdoFactory $factory,private AuthService $auth){}
 public function handle(string $report,string $uri): Response
 {
  $actor=$this->auth->current();Authorization::requireRole($actor,['Admin','Staff']);$filters=$report==='inventory'?['q','product_id','category_id','supplier_id','active','stock_status','low_stock']:['product_id','category_id','user_id','type','date_from','date_to'];$query=ApiInput::query(explode('?',$uri,2)[1]??'',[...$filters,'sort','direction','page','per_page','format']);$format=ApiInput::choice($query['format']??'json','format',['json','csv']);unset($query['format']);
  $result=(new ReportService($this->factory->connection()))->read($report,$query,$actor['role'],$format==='csv');if($format==='json')return Response::success($result['data'],$result['meta']);
  return new Response(200,ReportService::csv($report,$result['data']['items']),['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="bizstock-'.$report.'.csv"','Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff','X-Snapshot-At'=>$result['data']['snapshot_at']]);
 }
}
