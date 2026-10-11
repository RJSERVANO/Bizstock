<?php declare(strict_types=1); ?>
<section data-loading><?= $ui->component('state',['state'=>'loading','heading'=>'Loading records','message'=>'Fetching current database records.']) ?></section>
<section data-empty hidden><?= $ui->component('state',['state'=>'empty','heading'=>'No matching records','message'=>'Adjust the filters or add a record if you have permission.']) ?></section>
<section data-error hidden><?= $ui->component('state',['state'=>'error','heading'=>'Records could not be loaded','message'=>'Use Refresh to try again.','retryHref'=>'/'.$screen]) ?></section>
<div data-results class="panel table-panel" hidden><?= $ui->component('table',['caption'=>$title,'columns'=>$columns,'rows'=>[]]) ?></div>
<nav data-pagination aria-label="Pagination" hidden><span data-page-label></span><button class="bs-button bs-button--secondary" data-previous type="button">Previous</button><button class="bs-button bs-button--secondary" data-next type="button">Next</button></nav>
