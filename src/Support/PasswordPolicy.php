<?php
declare(strict_types=1);
namespace BizStock\Support;

final class PasswordPolicy
{
    public static function hash(string $password): string
    {
        // PASSWORD_DEFAULT currently uses bcrypt: reject lengths that would be silently truncated.
        if (strlen($password)<12 || strlen($password)>72 || str_contains($password,"\0") || !mb_check_encoding($password,'UTF-8') || trim($password)==='') {
            throw new \InvalidArgumentException('Password must be valid UTF-8, 12–72 bytes without NUL.');
        }
        return password_hash($password,PASSWORD_DEFAULT);
    }
}
