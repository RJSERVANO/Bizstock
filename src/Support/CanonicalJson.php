<?php
declare(strict_types=1);

namespace BizStock\Support;

final class CanonicalJson
{
    public static function encode(array $value): string
    {
        $normalize = static function (mixed $item) use (&$normalize): mixed {
            if (is_float($item) || is_object($item) || is_resource($item)) {
                throw new \InvalidArgumentException('Canonical payloads require scalar values and decimal strings, not floats or objects.');
            }
            if (!is_array($item)) {
                return $item;
            }
            if (!array_is_list($item)) {
                ksort($item, SORT_STRING);
            }
            return array_map($normalize, $item);
        };
        return json_encode($normalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
