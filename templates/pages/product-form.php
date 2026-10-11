<?php declare(strict_types=1); ?>
<div class="page-heading"><div><h1><?= \BizStock\Support\Ui::escape($title) ?></h1><p>Keep product details and stock rules together.</p></div></div>
<?php if (in_array($state, ['loading', 'empty', 'error', 'restricted'], true)): ?>
<section class="panel"><?= $ui->component('notice', ['tone' => $state === 'error' ? 'error' : 'info', 'heading' => ['loading' => 'Loading product details', 'empty' => 'Choose a product to edit', 'error' => 'Product details could not be loaded', 'restricted' => 'Product editing is restricted'][$state], 'message' => 'This fixture state does not submit or change product records.']) ?></section>
<?php else: ?>
<form class="form-layout" data-preview-form="product" data-unsaved-form novalidate>
<div data-form-errors tabindex="-1" role="alert" hidden></div>
<fieldset class="panel"><legend class="form-legend">Basic information</legend><div class="panel-body form-grid">
<?= $ui->component('field', ['id' => 'product-name', 'label' => 'Product name', 'required' => true, 'maxlength' => 150, 'value' => $mode === 'edit' ? 'A4 copy paper' : '', 'hint' => 'Use a clear, descriptive name.']) ?>
<?= $ui->component('field', ['id' => 'sku', 'label' => 'SKU', 'required' => true, 'maxlength' => 64, 'pattern' => '[A-Za-z0-9._-]+', 'value' => $mode === 'edit' ? 'OFF-001' : '', 'readonly' => $mode === 'edit', 'hint' => $mode === 'edit' ? 'SKU cannot change once movements exist.' : 'Unique identifier; letters, numbers, dots, underscores and hyphens.']) ?>
<?= $ui->component('field', ['id' => 'category', 'label' => 'Category', 'type' => 'select', 'required' => true, 'value' => $mode === 'edit' ? 'office' : '', 'options' => ['' => 'Choose category', 'office' => 'Office supplies', 'cleaning' => 'Cleaning', 'packaging' => 'Packaging']]) ?>
<?= $ui->component('field', ['id' => 'location', 'label' => 'Location', 'maxlength' => 100, 'hint' => 'Optional shelf or storage reference.']) ?>
<div class="span-full"><?= $ui->component('field', ['id' => 'description', 'label' => 'Description', 'type' => 'textarea', 'hint' => 'Optional details to help staff identify the item.']) ?></div>
</div></fieldset>
<fieldset class="panel"><legend class="form-legend">Supply and stock rules</legend><div class="panel-body form-grid">
<?= $ui->component('field', ['id' => 'supplier', 'label' => 'Primary supplier', 'type' => 'select', 'required' => true, 'value' => $mode === 'edit' ? 'central' : '', 'options' => ['' => 'Choose supplier', 'central' => 'Central Supply Co.', 'northline' => 'Northline Trading']]) ?>
<?= $ui->component('field', ['id' => 'price', 'label' => 'Reference unit price (PHP)', 'type' => 'number', 'step' => '0.01', 'max' => '99999999.99', 'required' => true, 'value' => $mode === 'edit' ? '245.00' : '', 'hint' => 'Reference unit value, not revenue or accounting valuation.']) ?>
<?= $ui->component('field', ['id' => 'threshold', 'label' => 'Minimum stock threshold', 'type' => 'number', 'required' => true, 'value' => '10', 'hint' => 'Low stock includes quantities at or below this value.']) ?>
<?= $ui->component('field', ['id' => $mode === 'edit' ? 'current-quantity' : 'opening-quantity', 'label' => $mode === 'edit' ? 'Current quantity' : 'Opening quantity', 'type' => 'number', 'value' => $mode === 'edit' ? '8' : '0', 'readonly' => $mode === 'edit', 'hint' => $mode === 'edit' ? 'Change stock through a movement, not this form.' : 'Opening stock will be recorded as an IN movement when persistence is implemented.']) ?>
</div></fieldset>
<div class="form-actions"><?= $ui->component('button', ['label' => 'Cancel', 'variant' => 'secondary', 'href' => '/preview/products']) ?><?= $ui->component('button', ['label' => 'Save product', 'type' => 'submit']) ?></div>
</form>
<?= $ui->component('dialog', ['id' => 'product-review', 'title' => 'Review product details', 'message' => 'Form validation succeeded in this preview. No product or opening movement was saved. Persistence will be connected in Stage 9.']) ?>
<?= $ui->component('dialog', ['id' => 'unsaved-review', 'title' => 'Leave this form?', 'message' => 'You have unsaved changes. Leaving will discard this preview form.']) ?>
<?php endif; ?>
