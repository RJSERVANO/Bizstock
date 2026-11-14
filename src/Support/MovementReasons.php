<?php
declare(strict_types=1);
namespace BizStock\Support;
final class MovementReasons
{
 public const CODES=['purchase_receipt'=>'Purchase receipt','customer_return'=>'Customer return','other_receipt'=>'Other receipt','sale_release'=>'Sale / release','supplier_return'=>'Supplier return','damage_writeoff'=>'Damage / write-off','internal_use'=>'Internal use','other_release'=>'Other release'];
 public static function validate(string $code,string $type):string{return ApiInput::choice($code,'reason_code',$type==='IN'?['purchase_receipt','customer_return','other_receipt']:['sale_release','supplier_return','damage_writeoff','internal_use','other_release']);}
}
