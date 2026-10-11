<?php
declare(strict_types=1);
use BizStock\Support\Ui;
$navigation = [
    ['Dashboard', 'dashboard', $prefix . '/dashboard', 'dashboard'],
    ['Products', 'box', $prefix . '/products', 'products'],
    ['Stock Movements', 'move', null, ''], ['History', 'history', null, ''], ['Reports', 'report', null, ''],
];
if (!$viewer) {
    $navigation = array_merge($navigation, [['Categories', 'lookup', null, ''], ['Suppliers', 'box', null, ''], ['Users', 'user', null, ''], ['Audit Log', 'history', null, '']]);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="app-base-path" content="<?= Ui::escape($config->basePath) ?>">
<title><?= Ui::escape($title) ?> · BizStock fixture preview</title>
<?php foreach (['vendor/bootstrap.min.css', 'css/tokens.css', 'css/components.css', 'css/layout.css'] as $asset): ?><link rel="stylesheet" href="<?= Ui::escape($config->url('/assets/' . $asset)) ?>"><?php endforeach; ?>
<script src="<?= Ui::escape($config->url('/assets/js/components.js')) ?>" defer></script>
<script src="<?= Ui::escape($config->url('/assets/js/api-client.js')) ?>" defer></script>
<script src="<?= Ui::escape($config->url('/assets/js/pages/preview.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<?php if ($screen === 'login'): ?>
<main id="main-content" class="login-page" tabindex="-1"><div class="login-wrap">
<a class="brand" href="<?= Ui::escape($config->url('/')) ?>"><span class="brand-mark"><?= $ui->icon('box') ?></span>BizStock</a>
<div class="preview-banner"><strong>Development preview.</strong> Fixture interface only. Sign-in is not connected.</div>
<?php require $config->root . '/templates/pages/login.php'; ?>
</div></main>
<?php else: ?>
<div class="app-shell">
<aside id="sidebar" class="sidebar" aria-label="Primary navigation">
<div class="drawer-heading"><a class="brand" href="<?= Ui::escape($config->url($prefix . '/dashboard')) ?>"><span class="brand-mark"><?= $ui->icon('box') ?></span>BizStock</a><button class="icon-button drawer-close" type="button" aria-label="Close navigation" data-drawer-close><?= $ui->icon('close') ?></button></div>
<p class="nav-section-label">Workspace</p><nav aria-label="Main">
<?php foreach ($navigation as [$label, $icon, $href, $key]): ?>
<?php if ($href !== null): ?><a class="nav-link" href="<?= Ui::escape($config->url($href)) ?>" <?= $screen === $key || ($key === 'products' && $screen === 'product-form') ? 'aria-current="page"' : '' ?>><?= $ui->icon($icon) ?><?= Ui::escape($label) ?></a>
<?php elseif (!$viewer): ?><span class="nav-unavailable"><?= $ui->icon($icon) ?><?= Ui::escape($label) ?><small>Planned</small></span><?php endif; ?>
<?php endforeach; ?>
</nav><p class="nav-section-label">Preview tools</p><nav aria-label="Development previews"><a class="nav-link" href="<?= Ui::escape($config->url('/preview/components')) ?>" <?= $screen === 'gallery' ? 'aria-current="page"' : '' ?>><?= $ui->icon('lookup') ?>Components</a><a class="nav-link" href="<?= Ui::escape($config->url('/preview/login')) ?>"><?= $ui->icon('user') ?>Login preview</a></nav>
<div class="sidebar-account"><strong><?= $viewer ? 'Availability preview' : 'Business owner preview' ?></strong><span><?= Ui::escape($role) ?> fixture · no active session</span></div>
</aside>
<div class="drawer-backdrop" data-drawer-backdrop hidden></div>
<div class="workspace" id="workspace">
<header class="topbar"><div class="topbar-leading"><button class="icon-button mobile-nav-trigger" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false" data-drawer-open><?= $ui->icon('menu') ?></button><p>Business inventory</p></div><a class="gallery-link" href="<?= Ui::escape($config->url('/preview/components')) ?>">Component library</a></header>
<main class="content" id="main-content" tabindex="-1">
<div class="preview-banner"><strong>Development preview.</strong> Sample data for design review. Nothing here changes inventory or signs you in.</div>
<?php require $config->root . '/templates/pages/' . $screen . '.php'; ?>
</main></div></div>
<?php endif; ?>
<div class="visually-hidden" id="ui-announcement" role="status" aria-live="polite"></div>
<?= $ui->component('dialog', ['id' => 'archive-review', 'title' => 'Review product archive', 'message' => 'Archiving will require zero stock and preserve movement history. This preview does not archive any product.']) ?>
</body></html>
