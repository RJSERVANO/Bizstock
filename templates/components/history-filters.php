<?php declare(strict_types=1); ?>
<?= $ui->component('field',['id'=>'product_id','label'=>'Product ID','type'=>'number','hint'=>'Optional; includes archived products.']) ?>
<?= $ui->component('field',['id'=>'category_id','label'=>'Category','type'=>'select','options'=>[''=>'All categories']]) ?>
<?= $ui->component('field',['id'=>'user_id','label'=>'Actor ID','type'=>'number','hint'=>'Optional; includes inactive actors.']) ?>
<?= $ui->component('field',['id'=>'type','label'=>'Movement type','type'=>'select','options'=>[''=>'All types','IN'=>'IN','OUT'=>'OUT']]) ?>
<?= $ui->component('field',['id'=>'date_from','label'=>'From date (Manila)','type'=>'date','hint'=>'First day included.']) ?>
<?= $ui->component('field',['id'=>'date_to','label'=>'Through date (Manila)','type'=>'date','hint'=>'Last day included. Maximum range: 366 days.']) ?>
<?= $ui->component('field',['id'=>'sort','label'=>'Sort by','type'=>'select','options'=>['transaction_date'=>'Date','transaction_id'=>'ID','quantity'=>'Quantity']]) ?>
<?= $ui->component('field',['id'=>'direction','label'=>'Order','type'=>'select','options'=>['desc'=>'Newest / descending','asc'=>'Oldest / ascending']]) ?>
