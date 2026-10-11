<?php
declare(strict_types=1);

namespace BizStock\Http;

final readonly class Response
{
    public function __construct(public int $status, public string $body, public array $headers = []) {}

    public static function json(array $body, int $status = 200, array $headers = []): self
    {
        return new self($status, json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            ['Content-Type' => 'application/json; charset=utf-8'] + $headers);
    }

    public static function success(mixed $data,array $meta=[],int $status=200): self
    {return self::json(['data'=>$data,'meta'=>$meta===[]?(object)[]:$meta],$status,['Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff']);}

    public function send(bool $head = false): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        if (!$head) {
            echo $this->body;
        }
    }
}
