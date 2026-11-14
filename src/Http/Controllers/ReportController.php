<?php
declare(strict_types=1);namespace BizStock\Http\Controllers;
use BizStock\Support\{PdoFactory,ApiInput};use BizStock\Services\{AuthService,ReportService};use BizStock\Http\{Authorization,Response};
final readonly class ReportController
{
 public function __construct(private PdoFactory $factory,private AuthService $auth){}
 public function handle(string $report,string $uri): Response
 {
  $actor=$this->auth->current();Authorization::requireRole($actor,['Admin','Staff']);$filters=$report==='inventory'?['q','product_id','category_id','supplier_id','active','stock_status','low_stock']:['product_id','category_id','user_id','type','reason_code','date_from','date_to'];$query=ApiInput::query(explode('?',$uri,2)[1]??'',[...$filters,'sort','direction','page','per_page','format']);$format=ApiInput::choice($query['format']??'json','format',['json','csv','detailed_json']);unset($query['format']);
  $result=(new ReportService($this->factory->connection()))->read($report,$query,$actor['role'],$format!=='json');if($format==='detailed_json'){$contextFilters=array_diff_key($query,array_flip(['page','per_page']));$contextFilters+=['sort'=>$report==='inventory'?'product_id':'transaction_date','direction'=>'asc'];if($report==='inventory')$contextFilters['active']='active';$payload=json_encode(['data'=>['schema'=>'bizstock.report.v2','report'=>$report,'filters'=>$contextFilters,'snapshot_at'=>$result['data']['snapshot_at'],'timezone'=>'Asia/Manila','timestamp_encoding'=>'UTC ISO-8601','unit_meaning'=>'Integer captured base unit. Null/unspecified means not recorded. Inventory uses current unit; historical movements retain captured unit.','scope'=>'All matching records; print remains current page.','items'=>$result['data']['items']],'meta'=>(object)[]],JSON_THROW_ON_ERROR);if(strlen($payload)>ReportService::EXPORT_BYTES)throw new \BizStock\Http\HttpException(422,'EXPORT_TOO_LARGE','Narrow the export to at most 16 MiB.');return new Response(200,$payload,['Content-Type'=>'application/json; charset=utf-8','Content-Disposition'=>'attachment; filename="bizstock-'.$report.'-detailed.json"','Cache-Control'=>'no-store']);}if($format==='json')return Response::success($result['data'],$result['meta']);
  return new Response(200,ReportService::csv($report,$result['data']['items']),['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="bizstock-'.$report.'.csv"','Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff','X-Snapshot-At'=>$result['data']['snapshot_at']]);
 }
}
