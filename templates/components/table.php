<?php
declare(strict_types=1);
use BizStock\Support\Ui;
?>
<div class="table-scroll" tabindex="0" role="region" aria-label="<?= Ui::escape($caption) ?>; scroll horizontally for more columns">
<table class="inventory-table<?= !empty($lookup) ? ' lookup-table' : '' ?>">
<caption class="visually-hidden"><?= Ui::escape($caption) ?></caption>
<thead><tr>
<?php foreach ($columns as $key => $label): ?><th scope="col" <?= in_array($key, ['quantity', 'threshold', 'price'], true) ? 'class="numeric"' : '' ?> <?= in_array($key, ['name', 'quantity'], true) ? 'aria-sort="none"' : '' ?>><?php if (in_array($key, ['name', 'quantity'], true)): ?><button type="button" class="sort-button" data-sort="<?= Ui::escape($key) ?>"><?= Ui::escape($label) ?><span aria-hidden="true" data-sort-symbol>↕</span></button><?php else: ?><?= Ui::escape($label) ?><?php endif; ?></th><?php endforeach; ?>
</tr></thead>
<tbody>
<?php foreach ($rows as $row): ?><tr data-product-row data-name="<?= Ui::escape($row['name']) ?>" data-sku="<?= Ui::escape($row['sku']) ?>" data-category="<?= Ui::escape($row['category']) ?>" data-status="<?= Ui::escape($row['status']) ?>" data-quantity="<?= (int) $row['quantity'] ?>">
<?php foreach ($columns as $key => $label): ?><td <?= in_array($key, ['quantity', 'threshold', 'price'], true) ? 'class="numeric"' : '' ?>>
<?php if ($key === 'status'): ?><?= $ui->component('badge', ['status' => $row['status']]) ?>
<?php elseif ($key === 'actions'): ?><details class="row-actions"><summary aria-label="Actions for <?= Ui::escape($row['name']) ?>">Actions</summary><a href="<?= Ui::escape($ui->config->url('/preview/products/new?mode=edit')) ?>">Edit fixture</a><button type="button" data-open-dialog="archive-review">Review archive</button></details>
<?php elseif ($key === 'price'): ?>₱<?= Ui::escape($row[$key]) ?>
<?php elseif ($key === 'name'): ?><?= Ui::escape($row['name']) ?><span class="mobile-stock-summary"><?= $row['quantity'] ?> available · threshold <?= $row['threshold'] ?></span>
<?php else: ?><?= Ui::escape($row[$key]) ?><?php endif; ?>
</td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></div>
