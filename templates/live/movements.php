<?php declare(strict_types=1); ?>
<form data-movement-form data-unsaved-form class="form-layout movement-form">
<div data-form-errors class="notice notice--error" role="alert" tabindex="-1" hidden></div>
<?php if($admin): ?><?= $ui->component('field',['id'=>'mode','label'=>'Movement mode','type'=>'select','options'=>['normal'=>'Normal IN / OUT','reverse'=>'Full reversal']]) ?><?php endif; ?>
<div class="movement-columns"><div class="movement-entry"><section data-normal class="panel form-grid"><h2 class="span-full">Movement details</h2>
<div class="product-search-row"><?= $ui->component('field',['id'=>'product_search','label'=>'Find product by name or SKU','type'=>'search','maxlength'=>100]) ?>
<button data-find type="button" class="bs-button bs-button--secondary">Find products</button></div>
<p class="movement-selection" data-selection-summary role="status">Search for a product, then choose a match below.</p>
<?= $ui->component('field',['id'=>'product_id','label'=>'Product','type'=>'select','required'=>true,'options'=>[''=>'Choose a product']]) ?>
<?= $ui->component('field',['id'=>'type','label'=>'Movement type','type'=>'select','options'=>['IN'=>'IN — add stock','OUT'=>'OUT — remove stock']]) ?>
<?= $ui->component('field',['id'=>'quantity','label'=>'Quantity','type'=>'number','required'=>true,'min'=>1,'value'=>1,'hint'=>'Enter the number of units to move.']) ?>
</section>
<?php if($admin): ?><section data-reversal class="panel form-grid" hidden><?= $ui->component('field',['id'=>'original_id','label'=>'Original movement ID','type'=>'number','hint'=>'Select an original from History. Full quantity and original price are preserved.']) ?><div><button data-original type="button" class="bs-button bs-button--secondary">Load original</button></div><div data-original-summary></div></section><?php endif; ?>
<section class="panel panel-body"><h2>Notes and reason</h2><?= $ui->component('field',['id'=>'notes','label'=>'Note / reversal reason','type'=>'textarea','hint'=>'Optional for IN/OUT. Required for reversal.']) ?></section>
<div class="form-actions"><button class="bs-button bs-button--primary" type="submit">Review movement</button></div>
</div><section class="panel availability-panel"><h2>Available stock</h2><p data-availability role="status">Select a product to load its current availability.</p><button data-availability-refresh type="button" class="bs-button bs-button--secondary">Refresh availability</button><p class="muted">Review the quantity and product before confirming. Stock is checked again when you save.</p></section></div>
</form><a href="<?= \BizStock\Support\Ui::escape($config->url('/history')) ?>">View stock history</a>
