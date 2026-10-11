<?php
declare(strict_types=1);
use BizStock\Support\Ui;
?>
<dialog class="bs-dialog" id="<?= Ui::escape($id) ?>" aria-labelledby="<?= Ui::escape($id) ?>-title">
<div class="dialog-heading"><h2 id="<?= Ui::escape($id) ?>-title"><?= Ui::escape($title) ?></h2><button type="button" class="icon-button" aria-label="Close dialog" data-close-dialog><?= $ui->icon('close') ?></button></div>
<p><?= Ui::escape($message) ?></p><p data-dialog-summary></p>
<div class="dialog-actions"><?= $ui->component('button', ['label' => 'Close review', 'variant' => 'secondary']) ?><?php if(!empty($confirmLabel)): ?><?= $ui->component('button',['label'=>$confirmLabel,'variant'=>'danger']) ?><?php endif; ?></div>
</dialog>
