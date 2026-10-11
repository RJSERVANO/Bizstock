<?php declare(strict_types=1); use BizStock\Support\Ui; ?>
<div class="role-cards">
<?php foreach([
['Admin','users','Manage products, suppliers, categories and accounts. Review reports, audit changes and correct stock movements.'],
['Staff','move','Record stock IN and OUT. Read product and supplier information, movement history and reports.'],
['Viewer','user','Check product availability and low-stock alerts. Sensitive prices, suppliers, movements and reports are restricted.']
] as [$role,$icon,$description]): ?>
<article class="role-card"><span class="public-icon"><?= $ui->icon($icon) ?></span><div><h3><?= Ui::escape($role) ?></h3><p><?= Ui::escape($description) ?></p><a href="<?= Ui::escape($config->url('/login').'?role='.$role) ?>#auth-title">Sign in as <?= Ui::escape($role) ?></a></div></article>
<?php endforeach; ?>
</div>
