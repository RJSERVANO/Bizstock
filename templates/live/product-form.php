<?php declare(strict_types=1); ?>
<section data-loading><?= $ui->component('state',['state'=>'loading','heading'=>'Loading product form','message'=>'Loading categories, suppliers and current metadata.']) ?></section>
<form data-product-form data-unsaved-form class="form-layout" hidden>
<a class="form-back" href="<?= \BizStock\Support\Ui::escape($config->url('/products')) ?>">← Back to products</a>
<div data-form-errors class="notice notice--error" role="alert" tabindex="-1" hidden></div>
<fieldset class="panel"><legend class="form-legend">Product details</legend><div class="form-grid">
<?php foreach([['sku','SKU',64],['product_name','Product name',150],['location','Location (optional)',100]] as [$key,$label,$max]): ?><?= $ui->component('field',['id'=>$key,'label'=>$label,'required'=>$key!=='location','maxlength'=>$max]) ?><?php endforeach; ?>
<?= $ui->component('field',['id'=>'category_id','label'=>'Category','type'=>'select','required'=>true,'options'=>[''=>'Choose a category']]) ?>
<?= $ui->component('field',['id'=>'supplier_id','label'=>'Supplier','type'=>'select','required'=>true,'options'=>[''=>'Choose a supplier']]) ?>
<?= $ui->component('field',['id'=>'description','label'=>'Description (optional)','type'=>'textarea']) ?>
</div></fieldset>
<fieldset class="panel"><legend class="form-legend">Stock and pricing</legend><div class="form-grid">
<?= $ui->component('field',['id'=>'price','label'=>'Unit price (PHP)','required'=>true,'pattern'=>'(0|[1-9][0-9]{0,7})\.[0-9]{2}','hint'=>'Enter the unit price with two decimal places.']) ?>
<?= $ui->component('field',['id'=>'minimum_threshold','label'=>'Minimum threshold','type'=>'number','required'=>true,'value'=>10]) ?>
<?php if($id): ?><?= $ui->component('field',['id'=>'current_quantity','label'=>'Current quantity','type'=>'number','readonly'=>true,'hint'=>'Use stock movements to change this balance.']) ?><?php else: ?><?= $ui->component('field',['id'=>'opening_quantity','label'=>'Opening quantity','type'=>'number','value'=>0,'required'=>true,'hint'=>'Your opening stock is recorded when the product is saved.']) ?><?php endif; ?>
</div></fieldset><div class="form-actions"><a class="bs-button bs-button--secondary" href="<?= \BizStock\Support\Ui::escape($config->url('/products')) ?>">Cancel</a><?= $ui->component('button',['label'=>'Save product','type'=>'submit']) ?></div>
</form>

<?php if($id): $imageEndpoint='/products/'.$id.'/image'; require $config->root.'/templates/components/image-editor.php'; else: ?><p class="muted">Save the product first, then edit it to add an image.</p><?php endif; ?>
