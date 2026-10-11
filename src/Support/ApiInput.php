<?php
declare(strict_types=1);
namespace BizStock\Support;
use BizStock\Http\HttpException;

final class ApiInput
{
    public const MAX_BODY_BYTES=65536;
    public static function body(array $allowed): array
    {
        $body=file_get_contents('php://input',false,null,0,self::MAX_BODY_BYTES+1);
        if($body===false){throw new HttpException(400,'BAD_REQUEST','Cannot read request body.');}
        return self::json($body,$_SERVER['CONTENT_TYPE']??'',$allowed);
    }
    public static function query(string $raw,array $allowed): array
    {
        if(strlen($raw)>4096){throw new HttpException(414,'URI_TOO_LONG','Query is too long.');}
        $values=[];
        foreach($raw===''?[]:explode('&',$raw) as $pair){
            [$key,$value]=array_pad(explode('=',$pair,2),2,'');
            if(preg_match('/%(?![0-9a-f]{2})/i',$pair)){self::invalid('query','Malformed query encoding.');}
            $key=urldecode($key);$value=urldecode($value);
            if(!in_array($key,$allowed,true) || array_key_exists($key,$values)){self::invalid('query','Unknown or repeated query field.');}
            if(!mb_check_encoding($value,'UTF-8') || preg_match('/[\x00-\x1f\x7f]/',$value)){self::invalid($key,'Invalid text.');}
            $values[$key]=$value;
        }
        return $values;
    }
    public static function json(string $body,string $contentType,array $allowed): array
    {
        if(strlen($body)>self::MAX_BODY_BYTES){throw new HttpException(413,'PAYLOAD_TOO_LARGE','Request body is too large.');}
        if(!preg_match('~^application/json(?:\s*;\s*charset=utf-8)?$~iD',trim($contentType))){throw new HttpException(415,'UNSUPPORTED_MEDIA_TYPE','Use application/json.');}
        try{$value=json_decode($body,false,32,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new HttpException(400,'BAD_REQUEST','Malformed JSON object.');}
        if(!$value instanceof \stdClass){throw new HttpException(400,'BAD_REQUEST','Expected a JSON object.');}
        // json_decode would silently retain the last duplicate key, making payload hashes ambiguous.
        preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|[{}\[\]:,]/s',$body,$matches);
        $stack=[];$tokens=$matches[0];
        foreach($tokens as $index=>$token){
            if($token==='{'||$token==='['){$stack[]=['object'=>$token==='{','keys'=>[]];}
            elseif($token==='}'||$token===']'){array_pop($stack);}
            elseif(str_starts_with($token,'"')&&($tokens[$index+1]??'')===':'&&($stack[array_key_last($stack)]['object']??false)){
                $key=json_decode($token,true,32,JSON_THROW_ON_ERROR);$top=array_key_last($stack);
                if(isset($stack[$top]['keys'][$key])){throw new HttpException(400,'BAD_REQUEST','Duplicate JSON field.');}
                $stack[$top]['keys'][$key]=true;
            }
        }
        $fields=get_object_vars($value);
        foreach($fields as $key=>$item){if(!in_array($key,$allowed,true)){self::invalid('body','Unknown request field.');}}
        return $fields;
    }
    public static function integer(mixed $value,string $field,int $min=0,int $max=2147483647): int
    {
        if(!(is_int($value) || (is_string($value)&&preg_match('/^(0|[1-9][0-9]*)$/D',$value))) || strlen((string)$value)>10 || (int)$value<$min || (int)$value>$max){self::invalid($field,'Invalid integer.');}
        return (int)$value;
    }
    public static function decimal(mixed $value,string $field): string
    {
        if(!is_string($value)||!preg_match('/^(0|[1-9][0-9]{0,7})\.[0-9]{2}$/D',$value)){self::invalid($field,'Use a bounded decimal string with two decimal places.');}
        return $value;
    }
    public static function text(mixed $value,string $field,int $max,bool $required=true): string
    {
        if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$max||preg_match('/[\x00-\x1f\x7f]/',$value)||($required&&trim($value)==='')){self::invalid($field,'Invalid text.');}
        return trim($value);
    }
    public static function page(array $query,array $sortMap,string $defaultSort): array
    {
        $page=self::integer($query['page']??'1','page',1,10000);
        $size=self::integer($query['per_page']??'25','per_page',1,100);
        $sort=$query['sort']??$defaultSort;$direction=$query['direction']??'asc';
        if(!is_string($sort)||!isset($sortMap[$sort])){self::invalid('sort','Unsupported sort field.');}
        if(!in_array($direction,['asc','desc'],true)){self::invalid('direction','Use asc or desc.');}
        return ['page'=>$page,'per_page'=>$size,'offset'=>($page-1)*$size,'sort'=>$sort,'order_by'=>$sortMap[$sort],'direction'=>strtoupper($direction)];
    }
    public static function operationKey(mixed $value): string
    {
        if(!is_string($value)||!preg_match('/^[A-Za-z0-9._:-]{1,128}$/D',$value)){self::invalid('Idempotency-Key','Invalid operation key.');}
        return $value;
    }
    public static function boolean(mixed $value,string $field): bool
    {
        if(!in_array($value,[true,false,'true','false'],true)){self::invalid($field,'Use true or false.');}
        return $value===true||$value==='true';
    }
    public static function choice(mixed $value,string $field,array $allowed): string
    {if(!is_string($value)||!in_array($value,$allowed,true)){self::invalid($field,'Unsupported value.');}return $value;}
    public static function dateRange(array $query): array
    {
        $dates=[];
        foreach(['date_from','date_to'] as $field){
            if(!isset($query[$field])){continue;}$value=$query[$field];
            if(!is_string($value)||!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D',$value,$parts)||!checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1])){self::invalid($field,'Use a valid YYYY-MM-DD date.');}
            $dates[$field]=$value;
        }
        if(isset($dates['date_from'],$dates['date_to'])){
            $from=new \DateTimeImmutable($dates['date_from'],new \DateTimeZone('Asia/Manila'));$to=new \DateTimeImmutable($dates['date_to'],new \DateTimeZone('Asia/Manila'));
            if($to<$from||(int)$from->diff($to)->days>365){self::invalid('date_to','Use an ordered range of at most 366 inclusive days.');}
        }
        return $dates;
    }
    private static function invalid(string $field,string $message): never
    {throw new HttpException(422,'VALIDATION_ERROR','Request validation failed.',[$field=>[$message]]);}
}
