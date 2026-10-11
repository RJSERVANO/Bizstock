<?php
declare(strict_types=1);
use BizStock\Support\Ui;
$copy = ['loading' => ['Loading inventory', 'Please wait while the inventory list is prepared.'],
    'empty' => ['No products yet', 'Products will appear here once they have been added.'],
    'error' => ['Inventory could not be loaded', 'Try again. Your stock records have not changed.'],
    'restricted' => ['Availability access only', 'This view does not include prices, suppliers or movement history.']];
[$defaultHeading, $defaultMessage] = $copy[$state] ?? $copy['empty'];
$heading=$heading??$defaultHeading; $message=$message??$defaultMessage;
?>
<div class="data-state" <?= $state === 'error' ? 'role="alert"' : 'role="status"' ?> <?= $state === 'loading' ? 'aria-busy="true"' : '' ?>>
<?php if ($state === 'loading'): ?><span class="loading-indicator" aria-hidden="true"></span><?php endif; ?>
<h3><?= Ui::escape($heading) ?></h3><p><?= Ui::escape($message) ?></p>
<?php if ($state === 'error'): ?><?= $ui->component('button', ['label' => 'Try again', 'variant' => 'secondary', 'href' => $retryHref??($prefix . '/' . ($screen === 'dashboard' ? 'dashboard' : 'products'))]) ?><?php endif; ?>
</div>
