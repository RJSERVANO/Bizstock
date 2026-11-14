<?php
declare(strict_types=1);namespace BizStock\Support;
final class BaseUnits
{
 public const LABELS=['unspecified'=>'Unspecified','piece'=>'Pieces','box'=>'Boxes','kg'=>'Whole kilograms','litre'=>'Whole litres','metre'=>'Whole metres','pair'=>'Pairs','set'=>'Sets'];
 public static function validate(mixed $value):string{return ApiInput::choice($value,'unit_code',array_keys(self::LABELS));}
}
