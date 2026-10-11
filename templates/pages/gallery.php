<?php declare(strict_types=1); ?>
<div class="page-heading"><div><h1>Component library</h1><p>Shared controls and states for consistent inventory screens.</p></div></div>
<div class="gallery">
<section class="panel"><div class="panel-heading"><h2>Buttons and confirmation</h2></div><div class="panel-body gallery-row">
<?php foreach (['primary', 'secondary', 'danger', 'ghost'] as $variant): ?><?= $ui->component('button', ['label' => ucfirst($variant), 'variant' => $variant, 'dialog' => 'gallery-review']) ?><?php endforeach; ?>
<?= $ui->component('button', ['label' => 'Disabled', 'disabled' => true]) ?><?= $ui->component('button', ['label' => 'Saving…', 'busy' => true]) ?>
</div></section>
<section class="panel"><div class="panel-heading"><h2>Fields and validation</h2></div><div class="panel-body"><form data-preview-form="gallery" novalidate><div data-form-errors tabindex="-1" role="alert" hidden></div><div class="form-grid">
<?= $ui->component('field', ['id' => 'gallery-name', 'label' => 'Product name', 'required' => true, 'hint' => 'A reusable labeled input.', 'maxlength' => 150]) ?>
<?= $ui->component('field', ['id' => 'gallery-select', 'label' => 'Category', 'type' => 'select', 'options' => ['office' => 'Office supplies', 'packaging' => 'Packaging']]) ?>
</div><?= $ui->component('button', ['label' => 'Check fields', 'type' => 'submit', 'variant' => 'secondary']) ?></form></div></section>
<section class="panel"><div class="panel-heading"><h2>Stock status</h2></div><div class="panel-body gallery-row"><?php foreach (['in', 'low', 'out'] as $status): ?><?= $ui->component('badge', compact('status')) ?><?php endforeach; ?></div></section>
<section class="panel"><div class="panel-heading"><h2>Alerts</h2></div><div class="panel-body gallery">
<?= $ui->component('notice', ['heading' => 'Information', 'message' => 'Stock changes are recorded as movements.']) ?>
<?= $ui->component('notice', ['tone' => 'error', 'heading' => 'Action could not be completed', 'message' => 'Check the highlighted fields and try again.']) ?>
<?= $ui->component('notice', ['tone' => 'success', 'heading' => 'Success message sample', 'message' => 'This is a component example, not a saved operation.']) ?>
</div></section>
<section class="panel"><div class="panel-heading"><h2>Data states</h2></div><div class="panel-body gallery-row">
<?php foreach (['populated', 'loading', 'empty', 'error', 'restricted'] as $example): ?><?= $ui->component('button', ['label' => ucfirst($example) . ' inventory', 'variant' => 'secondary', 'href' => '/preview/products?state=' . $example]) ?><?php endforeach; ?>
</div><?php foreach (['loading', 'empty', 'error', 'restricted'] as $state): ?><?= $ui->component('state', ['state' => $state, 'prefix' => '/preview', 'screen' => 'products']) ?><?php endforeach; ?></section>
</div>
<?= $ui->component('dialog', ['id' => 'gallery-review', 'title' => 'Confirm an action', 'message' => 'Dialogs keep focus inside, support Escape and return focus to the opening control. This example changes no records.']) ?>
