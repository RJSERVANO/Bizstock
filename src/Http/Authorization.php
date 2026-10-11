<?php
declare(strict_types=1);
namespace BizStock\Http;

final class Authorization
{
    public static function requireRole(array $user, array $roles): void
    {
        if (!in_array($user['role'],$roles,true)) { throw new HttpException(403,'FORBIDDEN','Access denied.'); }
    }

    public static function user(array $row): array
    {
        $safe=array_intersect_key($row,array_flip(['user_id','full_name','username','role','is_active','must_change_password','created_at','updated_at']));
        $safe['user_id']=(int)$safe['user_id'];
        $safe['has_image']=!empty($row['profile_image']);
        foreach (['is_active','must_change_password'] as $key) { $safe[$key]=(bool)$safe[$key]; }
        foreach (['created_at','updated_at'] as $key) { $safe[$key]=(new \DateTimeImmutable($safe[$key],new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'); }
        return $safe;
    }

    public static function product(array $row, string $role): array
    {
        if (!in_array($role,['Admin','Staff','Viewer'],true)) { throw new HttpException(403,'FORBIDDEN','Access denied.'); }
        $fields=['product_id','sku','product_name','category_id','category_name','quantity','minimum_threshold','stock_status','is_active'];
        if ($role!=='Viewer') { $fields=array_merge($fields,['price','supplier_id','supplier_name','location','description','version','created_at','updated_at']); }
        return array_intersect_key($row,array_flip($fields))+['has_image'=>!empty($row['product_image'])];
    }

    public static function category(array $row): array
    { return array_intersect_key($row,array_flip(['category_id','category_name'])); }

    // Security gates precede future handlers. An allowed request still gets 404 until its stage.
    public static function plannedRoutes(): array
    {
        $all=['Admin','Staff','Viewer']; $business=['Admin','Staff']; $admin=['Admin'];
        return [
            '/api/v1/dashboard'=>['GET'=>$all],
            '/api/v1/products'=>['GET'=>$all,'POST'=>$admin],
            '/api/v1/products/{id}'=>['GET'=>$all,'PATCH'=>$admin,'DELETE'=>$admin],
            '/api/v1/categories'=>['GET'=>$all,'POST'=>$admin],
            '/api/v1/categories/{id}'=>['PATCH'=>$admin,'DELETE'=>$admin],
            '/api/v1/suppliers'=>['GET'=>$business,'POST'=>$admin],
            '/api/v1/suppliers/{id}'=>['PATCH'=>$admin,'DELETE'=>$admin],
            '/api/v1/transactions'=>['GET'=>$business,'POST'=>$business],
            '/api/v1/transactions/{id}'=>['GET'=>$business],
            '/api/v1/reports/inventory'=>['GET'=>$business],
            '/api/v1/reports/transactions'=>['GET'=>$business],
            '/api/v1/users'=>['GET'=>$admin,'POST'=>$admin],
            '/api/v1/users/{id}'=>['PATCH'=>$admin,'DELETE'=>$admin],
            '/api/v1/users/{id}/password-reset'=>['POST'=>$admin],
            '/api/v1/audit-events'=>['GET'=>$admin],
        ];
    }
}
