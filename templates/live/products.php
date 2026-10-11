<?php declare(strict_types=1); ?>
<form data-search class="panel compact-toolbar">
<?= $ui->component('field',['id'=>'q','label'=>'Search name or SKU','type'=>'search','maxlength'=>100]) ?>
<?= $ui->component('field',['id'=>'category_id','label'=>'Category','type'=>'select','options'=>[''=>'All categories']]) ?>
<?php if(!$viewer): ?><?= $ui->component('field',['id'=>'supplier_id','label'=>'Supplier','type'=>'select','options'=>[''=>'All suppliers']]) ?><?php endif; ?>
<?= $ui->component('field',['id'=>'stock_status','label'=>'Stock status','type'=>'select','options'=>[''=>'All statuses','in_stock'=>'In stock','low_stock'=>'Low stock','out_of_stock'=>'Out of stock']]) ?>
<?= $ui->component('field',['id'=>'active','label'=>'Products','type'=>'select','options'=>['active'=>'Active','archived'=>'Archived','all'=>'All']]) ?>
<?= $ui->component('field',['id'=>'low_stock','label'=>'Threshold alert','type'=>'select','options'=>[''=>'All availability','true'=>'At or below threshold (including zero)']]) ?>
<?= $ui->component('field',['id'=>'sort','label'=>'Sort by','type'=>'select','options'=>['product_id'=>'ID','product_name'=>'Name','sku'=>'SKU','quantity'=>'Quantity']+(!$viewer?['price'=>'Price']:[])]) ?>
<?= $ui->component('field',['id'=>'direction','label'=>'Order','type'=>'select','options'=>['asc'=>'Ascending','desc'=>'Descending']]) ?>
<div><?= $ui->component('button',['label'=>'Search','type'=>'submit']) ?> <button class="bs-button bs-button--secondary" data-refresh type="button">Refresh</button></div></form>
<?php $columns=['catalogue_sku'=>'SKU','catalogue_name'=>'Name','catalogue_category'=>'Category','quantity'=>'Quantity','threshold'=>'Threshold','catalogue_status'=>'Status']+(!$viewer?['price'=>'Price (PHP)','catalogue_supplier'=>'Supplier']:[])+['catalogue_actions'=>'Actions'];require $config->root.'/templates/components/live-results.php'; ?>
