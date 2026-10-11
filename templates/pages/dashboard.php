<?php
declare(strict_types=1);
use BizStock\Support\Ui;
?>
<div class="page-heading"><div><h1>Dashboard</h1><p>A clear view of your inventory and items that need attention.</p></div><div class="heading-actions"><?= $ui->component('button', ['label' => 'View products', 'variant' => 'secondary', 'href' => $prefix . '/products']) ?></div></div>
<?php if (in_array($state, ['loading', 'empty', 'error'], true)): ?>
<section class="panel" aria-label="Dashboard inventory state"><?= $ui->component('state', compact('state', 'prefix', 'screen')) ?></section>
<?php else: ?>
<?php if ($viewer || $state === 'restricted'): ?><?= $ui->component('notice', ['heading' => 'Availability view', 'message' => 'Prices, suppliers and movement history are hidden in this view.']) ?><div class="mb-4"></div><?php endif; ?>
<div class="metrics">
<?php foreach ([['Active products', count($products), 'In the sample catalogue'], ['Total units', array_sum(array_column($products, 'quantity')), 'Across active sample products'], ['Low-stock products', count($lowProducts), 'Includes out-of-stock products']] as [$label, $value, $hint]): ?>
<section class="panel metric-card"><h2><?= Ui::escape($label) ?></h2><p class="metric-value"><?= Ui::escape($value) ?></p><p><?= Ui::escape($hint) ?></p></section>
<?php endforeach; ?></div>
<div class="dashboard-grid <?= $viewer || $state === 'restricted' ? 'viewer-grid' : '' ?>">
<section class="panel" aria-labelledby="low-stock-title"><div class="panel-heading"><h2 id="low-stock-title">Low-stock attention</h2><a href="<?= Ui::escape($config->url($prefix . '/products?status=low')) ?>">View list</a></div><ul class="low-list">
<?php foreach ($lowProducts as $product): ?><li><div><strong><?= Ui::escape($product['name']) ?></strong><p><?= Ui::escape($product['sku']) ?> · <?= Ui::escape($product['category']) ?></p></div><div class="low-stock-meta"><?= $ui->component('badge', ['status' => $product['status']]) ?><p><span class="numeric"><?= $product['quantity'] ?></span> available · threshold <?= $product['threshold'] ?></p></div></li><?php endforeach; ?>
</ul></section>
<?php if (!$viewer && $state !== 'restricted'): ?>
<section class="panel" aria-labelledby="recent-title"><div class="panel-heading"><h2 id="recent-title">Recent movements</h2><span class="muted small">Sample history</span></div><ul class="movement-list">
<li><div><strong>Shipping cartons, medium</strong><p>Stock IN · Owner fixture</p></div><div class="numeric"><strong>+20</strong><p>11 Oct, 9:30 AM</p></div></li>
<li><div><strong>A4 copy paper</strong><p>Stock OUT · Staff fixture</p></div><div class="numeric"><strong>−4</strong><p>11 Oct, 9:10 AM</p></div></li>
<li><div><strong>Clear packing tape</strong><p>Stock OUT · Staff fixture</p></div><div class="numeric"><strong>−2</strong><p>10 Oct, 4:15 PM</p></div></li>
</ul></section><?php endif; ?></div>
<p class="muted small mt-3">Fixture snapshot: 11 Oct 2026, 10:00 AM · Asia/Manila</p>
<?php endif; ?>
