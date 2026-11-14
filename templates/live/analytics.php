<?php declare(strict_types=1); ?>
<form data-search class="panel compact-toolbar task-toolbar analytics-toolbar" aria-label="Movement analytics filters">
<?= $ui->component('field',['id'=>'period','label'=>'Date shortcut','type'=>'select','value'=>'7','options'=>['7'=>'Last 7 days','30'=>'Last 30 days',''=>'Custom dates']]) ?>
<?= $ui->component('field',['id'=>'date_from','label'=>'From date (Manila)','type'=>'date']) ?>
<?= $ui->component('field',['id'=>'date_to','label'=>'Through date (Manila)','type'=>'date']) ?>
<?= $ui->component('field',['id'=>'group','label'=>'View','type'=>'select','options'=>['top'=>'Top outgoing products','inactive'=>'No operational movement','product'=>'Product movement summary','category'=>'Current-category movement summary','unit'=>'Quantities by captured unit']]) ?>
<button class="bs-button bs-button--primary" type="submit">Apply filters</button><button class="bs-button bs-button--secondary" type="button" data-refresh>Refresh</button>
</form>
<details class="methodology"><summary>How these figures are calculated</summary><div>
<p>Dates include both selected days in Manila, up to 366 days. Operational movement excludes opening stock and physical-count adjustments, including their reversals. Quantities use the unit captured at the time; incompatible units are never added together.</p>
<p>A reversal offsets the original direction on the reversal date: OUT 5 on November 1 and its reversal on November 8 contribute OUT 5 and OUT −5 respectively. Top products rank by net outgoing quantity, not value. Categories use current product assignments.</p>
<p>Unspecified means the unit was not recorded; classifying a product does not relabel history. Ambiguous historical receipts are excluded and flagged. Affected products are not labeled as having no movement; rankings may be incomplete.</p>
</div></details>
<section class="results-context" aria-label="Applied analytics context" data-analytics-metadata hidden>
<h2 data-analysis-title>Top outgoing products</h2><p data-analytics-context role="status"></p>
</section>
<p class="notice notice--warning" data-analytics-warning role="status" hidden></p>
<?php $columns=['analytics_product'=>'Product / group','analytics_unit'=>'Captured unit','analytics_in'=>'IN','analytics_out'=>'OUT','analytics_net'=>'Net (IN − OUT)'];require $config->root.'/templates/components/live-results.php'; ?>
