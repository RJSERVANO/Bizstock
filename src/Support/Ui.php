<?php
declare(strict_types=1);

namespace BizStock\Support;

final readonly class Ui
{
    public function __construct(public Config $config) {}

    public function asset(string $path): string
    {
        return $this->config->url('/assets/'.$path).'?v='.substr(hash_file('sha256',$this->config->root.'/public/assets/'.$path),0,12);
    }

    public static function escape(string|int $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function component(string $name, array $props = []): string
    {
        if (!in_array($name, ['button', 'field', 'badge', 'notice', 'state', 'pagination', 'dialog', 'table'], true)) {
            throw new \InvalidArgumentException('Unknown UI component.');
        }
        $ui = $this;
        extract($props, EXTR_SKIP);
        ob_start();
        try {
            require $this->config->root . '/templates/components/' . $name . '.php';
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function icon(string $name): string
    {
        $paths = [
            'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'box' => '<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v10l9 5 9-5V8M12 13v10"/>',
            'move' => '<path d="M4 7h16m-5-5 5 5-5 5M20 17H4m5-5-5 5 5 5"/>',
            'history' => '<path d="M3 11a9 9 0 1 1 2 7M3 3v8h8m1-5v6l4 2"/>',
            'report' => '<path d="M5 3h10l4 4v14H5V3Zm3 14h8M8 13h8M8 9h3"/>',
            'lookup' => '<path d="M4 5h16M4 12h16M4 19h16"/>',
            'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/>',
            'users' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v3"/>',
            'audit' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M8 4H5v17h14V4h-3"/><circle cx="12" cy="13" r="4"/><path d="M12 11v2l2 1"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'arrow' => '<path d="m9 5 7 7-7 7"/>',
        ];
        return '<svg data-icon="' . self::escape($name) . '" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . ($paths[$name] ?? $paths['box']) . '</svg>';
    }
}
