<?php
declare(strict_types=1);

namespace BizStock\Http\Controllers;

use BizStock\Http\HttpException;
use BizStock\Http\Response;
use BizStock\Support\Config;
use BizStock\Support\Ui;

final readonly class PreviewController
{
    public function __construct(private Config $config) {}

    public function show(string $path, string $uri): Response
    {
        if ($this->config->environment === 'production') {
            throw new HttpException(404, 'NOT_FOUND', 'Route not found.');
        }
        $screens = ['/preview/login' => 'login', '/preview/dashboard' => 'dashboard',
            '/preview/products' => 'products', '/preview/products/new' => 'product-form',
            '/preview/components' => 'gallery', '/preview/viewer/dashboard' => 'dashboard',
            '/preview/viewer/products' => 'products'];
        if (!isset($screens[$path])) {
            throw new HttpException(404, 'NOT_FOUND', 'Route not found.');
        }
        parse_str(explode('?', $uri, 2)[1] ?? '', $query);
        $state = $query['state'] ?? 'populated';
        if (!is_string($state) || !in_array($state, ['populated', 'loading', 'empty', 'error', 'restricted'], true)) {
            throw new HttpException(400, 'BAD_REQUEST', 'Unknown preview state.');
        }
        $mode = $query['mode'] ?? 'create';
        if (!is_string($mode) || !in_array($mode, ['create', 'edit'], true)) {
            throw new HttpException(400, 'BAD_REQUEST', 'Unknown preview mode.');
        }
        $config = $this->config;
        $ui = new Ui($config);
        $screen = $screens[$path];
        // Fixed fixture contexts, not a login or a production role-switch control.
        $viewer = str_starts_with($path, '/preview/viewer/');
        $prefix = $viewer ? '/preview/viewer' : '/preview';
        $role = $viewer ? 'Viewer' : 'Admin';
        $titles = ['login' => 'Sign in', 'dashboard' => 'Dashboard', 'products' => 'Products',
            'product-form' => $mode === 'edit' ? 'Edit product' : 'Add product', 'gallery' => 'Component library'];
        $title = $titles[$screen];
        $products = [
            ['sku' => 'OFF-001', 'name' => 'A4 copy paper', 'category' => 'Office supplies', 'quantity' => 8, 'threshold' => 10, 'price' => '245.00', 'supplier' => 'Central Supply Co.'],
            ['sku' => 'OFF-002', 'name' => 'Black ballpoint pens — pack of 12', 'category' => 'Office supplies', 'quantity' => 42, 'threshold' => 12, 'price' => '96.00', 'supplier' => 'Central Supply Co.'],
            ['sku' => 'CLN-001', 'name' => 'Multi-purpose cleaner, 1 litre', 'category' => 'Cleaning', 'quantity' => 0, 'threshold' => 5, 'price' => '135.00', 'supplier' => 'Northline Trading'],
            ['sku' => 'PKG-001', 'name' => 'Shipping cartons, medium', 'category' => 'Packaging', 'quantity' => 18, 'threshold' => 8, 'price' => '38.00', 'supplier' => 'Northline Trading'],
            ['sku' => 'PKG-002', 'name' => 'Clear packing tape', 'category' => 'Packaging', 'quantity' => 6, 'threshold' => 6, 'price' => '52.00', 'supplier' => 'Northline Trading'],
            ['sku' => 'OFF-003', 'name' => 'Document folders with reinforced tabs and extra-long descriptive fixture name', 'category' => 'Office supplies', 'quantity' => 24, 'threshold' => 10, 'price' => '18.50', 'supplier' => 'Central Supply Co.'],
        ];
        foreach ($products as &$product) {
            $product['status'] = $product['quantity'] === 0 ? 'out' : ($product['quantity'] <= $product['threshold'] ? 'low' : 'in');
            if ($viewer) {
                unset($product['price'], $product['supplier']);
            }
        }
        unset($product);
        $lowProducts = array_values(array_filter($products, static fn (array $p): bool => $p['quantity'] <= $p['threshold']));
        ob_start();
        try {
            require $config->root . '/templates/layouts/app.php';
            return new Response(200, (string) ob_get_contents(), ['Content-Type' => 'text/html; charset=utf-8']);
        } finally {
            ob_end_clean();
        }
    }
}
