<?php
declare(strict_types=1);
use BizStock\Support\Ui;
$tone = in_array($tone ?? 'info', ['info', 'error', 'success'], true) ? ($tone ?? 'info') : 'info';
?>
<div class="notice notice--<?= $tone ?>" <?= $tone === 'error' ? 'role="alert"' : 'role="status"' ?>><strong><?= Ui::escape($heading) ?></strong><p><?= Ui::escape($message) ?></p></div>
