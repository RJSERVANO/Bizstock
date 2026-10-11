<?php declare(strict_types=1);use BizStock\Support\Ui; ?>
<p class="nav-section-label">Overview</p>
<a class="nav-link" href="<?= Ui::escape($config->url('/dashboard')) ?>"><?= $ui->icon('dashboard') ?>Dashboard</a>
<a class="nav-link" href="<?= Ui::escape($config->url('/products')) ?>"><?= $ui->icon('box') ?>Products</a>
<p class="nav-section-label">Inventory</p><?php if($user['role']!=='Viewer'): ?><a class="nav-link" href="<?= Ui::escape($config->url('/movements')) ?>"><?= $ui->icon('move') ?>Stock movements</a><a class="nav-link" href="<?= Ui::escape($config->url('/history')) ?>"><?= $ui->icon('history') ?>History</a><?php endif; ?>
<a class="nav-link" href="<?= Ui::escape($config->url('/categories')) ?>"><?= $ui->icon('lookup') ?>Categories</a>
<?php if($user['role']!=='Viewer'): ?><a class="nav-link" href="<?= Ui::escape($config->url('/reports/inventory')) ?>"><?= $ui->icon('report') ?>Reports</a><?php endif; ?>
<?php if($user['role']!=='Viewer'): ?><a class="nav-link" href="<?= Ui::escape($config->url('/suppliers')) ?>"><?= $ui->icon('box') ?>Suppliers</a><?php endif; ?>
<a class="nav-link" href="<?= Ui::escape($config->url('/account')) ?>"><?= $ui->icon('user') ?>Your account</a>
<p class="nav-section-label">Workspace</p><?php if($user['role']==='Admin'): ?><a class="nav-link" href="<?= Ui::escape($config->url('/users')) ?>"><?= $ui->icon('users') ?>Users</a><a class="nav-link" href="<?= Ui::escape($config->url('/audit-events')) ?>"><?= $ui->icon('audit') ?>Audit log</a><?php endif; ?>
