<?php
declare(strict_types=1);
namespace BizStock\Support;
use BizStock\Repositories\ProductRepository;

final class ProductListState
{
    public static function query(string $raw,string $role): string
    {
        $query=ApiInput::query($raw,['q','category_id','supplier_id','active','stock_status','low_stock','page','per_page','sort','direction']);
        $query=array_filter($query,static fn($value)=>$value!=='');
        ProductRepository::filters($query,$role);
        ApiInput::page($query,['product_id'=>'product_id','product_name'=>'product_name','sku'=>'sku','quantity'=>'quantity']+($role==='Viewer'?[]:['price'=>'price']),'product_id');
        return http_build_query($query,'','&',PHP_QUERY_RFC3986);
    }

    public static function destination(string $candidate,Config $config,string $role): string
    {
        $fallback=$config->url('/products');
        $parts=explode('?',$candidate,2);
        if($parts[0]!==$fallback||str_contains($candidate,'#')||preg_match('/[\x00-\x20\x7f\\\\]/',$candidate))return $fallback;
        try{$query=self::query($parts[1]??'',$role);}catch(\BizStock\Http\HttpException){return $fallback;}
        return $fallback.($query!==''?'?'.$query:'');
    }
}
