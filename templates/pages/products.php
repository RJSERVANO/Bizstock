<?php
declare(strict_types=1);
$restricted = $viewer || $state === 'restricted';
$columns = ['sku' => 'SKU', 'name' => 'Product', 'category' => 'Category', 'quantity' => 'Quantity', 'threshold' => 'Threshold', 'status' => 'Stock status'];
if (!$restricted) {
    $columns += ['price' => 'Unit price', 'supplier' => 'Primary supplier', 'actions' => 'Actions'];
}
?>
<div class="page-heading"><div><h1>Products</h1><p>Search your catalogue and check current availability.</p></div><?php if (!$restricted): ?><div class="heading-actions"><?= $ui->component('button', ['label' => 'Add product', 'href' => '/preview/products/new', 'icon' => 'plus']) ?></div><?php endif; ?></div>
<?php if ($restricted): ?><?= $ui->component('notice', ['heading' => 'Availability view', 'message' => 'Product availability is visible. Prices, suppliers and editing actions are hidden.']) ?><div class="mb-4"></div><?php endif; ?>
<section class="panel" data-inventory data-page-size="4" aria-label="Product inventory">
<div class="filter-toolbar">
<?= $ui->component('field', ['id' => 'product-search', 'label' => 'Search products', 'type' => 'search', 'maxlength' => 150, 'hint' => 'Search by name or SKU']) ?>
<?= $ui->component('field', ['id' => 'category-filter', 'label' => 'Category', 'type' => 'select', 'options' => ['' => 'All categories', 'Office supplies' => 'Office supplies', 'Cleaning' => 'Cleaning', 'Packaging' => 'Packaging']]) ?>
<?= $ui->component('field', ['id' => 'status-filter', 'label' => 'Stock status', 'type' => 'select', 'options' => ['' => 'All statuses', 'low' => 'Low stock (including zero)', 'out' => 'Out of stock', 'in' => 'In stock']]) ?>
</div>
<?php if (in_array($state, ['loading', 'empty', 'error'], true)): ?>
<?= $ui->component('state', compact('state', 'prefix', 'screen')) ?>
<?php else: ?>
<?= $ui->component('table', ['caption' => 'Sample product availability', 'columns' => $columns, 'rows' => $products]) ?>
<p class="table-scroll-hint">Scroll within the table to see all columns. On a keyboard, focus the table and use arrow keys.</p>
<div class="data-state" data-no-results role="status" hidden><h2>No matching products</h2><p>Try a different name, SKU, category or stock status.</p><button type="button" class="bs-button bs-button--secondary" data-reset-filters>Clear filters</button></div>
<?= $ui->component('pagination') ?>
<?php endif; ?>
</section>
<p class="muted small mt-3">Sample data only. Quantity and threshold are separate values.<?= !$restricted ? ' Prices are reference unit values in PHP.' : '' ?></p>
