<?php
declare(strict_types=1);
use BizStock\Support\Ui;
$variant = in_array($variant ?? 'primary', ['primary', 'secondary', 'danger', 'ghost'], true) ? ($variant ?? 'primary') : 'primary';
$class = 'bs-button bs-button--' . $variant;
$type = ($type ?? 'button') === 'submit' ? 'submit' : 'button';
?>
<?php if (isset($href)): ?>
<a class="<?= Ui::escape($class) ?>" href="<?= Ui::escape($ui->config->url($href)) ?>"><?= isset($icon) ? $ui->icon($icon) : '' ?><?= Ui::escape($label) ?></a>
<?php else: ?>
<button class="<?= Ui::escape($class) ?>" type="<?= $type ?>" <?= !empty($disabled) || !empty($busy) ? 'disabled' : '' ?> <?= !empty($busy) ? 'aria-busy="true"' : '' ?> <?= isset($dialog) ? 'data-open-dialog="' . Ui::escape($dialog) . '"' : '' ?>><?= isset($icon) ? $ui->icon($icon) : '' ?><?= Ui::escape($label) ?></button>
<?php endif; ?>
