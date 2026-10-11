<?php declare(strict_types=1); use BizStock\Support\Ui;
$link=static fn(string $path):string=>Ui::escape($config->url($path));
$action=$link($destination);
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="BizStock helps your business organize products, record stock movements and see what needs attention.">
<title>BizStock · Know your stock</title>
<?php foreach(['vendor/bootstrap.min.css','css/tokens.css','css/components.css','css/layout.css','css/public.css'] as $asset): ?><link rel="stylesheet" href="<?= Ui::escape($ui->asset($asset)) ?>"><?php endforeach; ?>
</head><body class="public-site">
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="public-header"><div class="public-container header-inner">
<a class="brand" href="<?= $link('/') ?>" aria-label="BizStock home"><span class="brand-mark"><?= $ui->icon('box') ?></span><span>BizStock</span></a>
<nav aria-label="Public navigation"><a href="<?= $link('/') ?>#features">Features</a><a href="<?= $link('/') ?>#workflow">How it works</a><a href="<?= $link('/') ?>#about">About</a><a href="<?= $link('/') ?>#contact">Contact</a></nav>
<a class="public-button small-button" href="<?= $action ?>"><?= Ui::escape($cta) ?></a>
</div></header>
<main id="main-content" tabindex="-1">
<section class="public-hero"><div class="public-container hero-grid">
<div class="hero-copy"><p class="eyebrow">A clearer view of your business</p>
<h1>Know your stock. Run your business with confidence.</h1>
<p class="hero-description">Less guesswork. More control. Bring your products, stock movements and daily inventory decisions together in one organized workspace.</p>
<div class="public-actions"><a class="public-button" href="<?= $action ?>"><?= Ui::escape($cta) ?> <?= $ui->icon('arrow') ?></a><a class="text-link" href="<?= $link('/') ?>#features">Explore the features</a></div>
<p class="hero-note">Built for business owners and the teams who keep things moving.</p></div>
<div class="hero-visual">
<figure class="stockroom-photo"><img src="<?= $link('/assets/images/stockroom-960.webp') ?>" srcset="<?= $link('/assets/images/stockroom-480.webp') ?> 480w, <?= $link('/assets/images/stockroom-960.webp') ?> 960w, <?= $link('/assets/images/stockroom-1440.webp') ?> 1440w" sizes="(max-width:900px) 92vw, 48vw" width="1440" height="960" fetchpriority="high" alt="Illustration of business owners checking inventory together in an organized stockroom."><figcaption>AI-generated illustration · fictional people</figcaption></figure>
<div class="sample-preview" aria-label="Illustrative inventory preview"><div class="preview-heading"><span class="preview-icon"><?= $ui->icon('dashboard') ?></span><strong>Inventory at a glance</strong><span class="sample-label">Sample data</span></div>
<div class="sample-metrics"><div><span>Products</span><strong>3</strong></div><div><span>Units on hand</span><strong>42</strong></div><div><span>Needs attention</span><strong>1</strong></div></div>
<div class="sample-row"><span>Reusable tote bags<small>Packaging supplies</small></span><span class="sample-status">8 left · low stock</span></div>
<p>Illustrative preview. Your inventory stays private.</p></div>
</div></div></section>
<section id="features" class="public-section"><div class="public-container">
<div class="section-intro"><p class="eyebrow">Everything in its place</p><h2>The essentials for everyday inventory.</h2><p>See what you have, record what changes and find the information you need to make the next decision.</p></div>
<div class="feature-grid">
<?php foreach([
['box','Products, organized','Keep names, unique SKUs, images and stock thresholds together. Find products with search, filters and a table or card view.'],
['lookup','Categories & suppliers','Group your products and maintain the supplier records your team relies on.'],
['move','Stock IN & OUT','Record incoming and outgoing units with availability checks and a permanent movement history.'],
['dashboard','Low-stock attention','See products at or below their threshold, including items with no stock left.'],
['report','Reports you can use','Review current inventory and movement history. Filter results, export CSV or print the page.'],
['users','The right access','Admin manages the workspace, Staff records movements and Viewer checks product availability.']
] as [$icon,$heading,$copy]): ?>
<article class="feature-card"><span class="public-icon"><?= $ui->icon($icon) ?></span><h3><?= Ui::escape($heading) ?></h3><p><?= Ui::escape($copy) ?></p></article>
<?php endforeach; ?></div></div></section>
<section id="workflow" class="public-section workflow-section"><div class="public-container workflow-grid">
<div class="section-intro"><p class="eyebrow">How it works</p><h2>A simple rhythm for your working day.</h2><p>Keep the record up to date as stock arrives and leaves. Your dashboard and reports reflect the inventory your team actually records.</p><a class="text-link" href="<?= $action ?>"><?= Ui::escape($cta) ?> <?= $ui->icon('arrow') ?></a></div>
<ol class="workflow-steps"><li><span>01</span><div><h3>Set up your inventory</h3><p>Admin adds categories, suppliers and products, including any opening stock.</p></div></li><li><span>02</span><div><h3>Record each movement</h3><p>Admin or Staff selects the product, checks availability and confirms stock IN or OUT.</p></div></li><li><span>03</span><div><h3>Review and act</h3><p>Check alerts and availability. Admin and Staff can review history and export reports.</p></div></li></ol>
</div></section>
<section id="about" class="public-section"><div class="public-container about-grid"><div class="about-panel"><span class="public-icon"><?= $ui->icon('box') ?></span><p class="eyebrow">One workspace. A shared record.</p><h2>Clarity for the people behind the business.</h2><p>BizStock is a single-business inventory workspace. It brings product records, stock availability and movement history into a clear, shared view.</p><p>Designed for daily work, with separate permissions for owners, staff and viewers. Your administrator provides access to <?= Ui::escape($config->businessName) ?>.</p></div>
<div class="role-overview"><p class="eyebrow">Access for your team</p><h2>One sign-in. The right permissions.</h2><p>Each person uses their own administrator-provided username and password. Your account determines what you can see and do.</p>
<?php require $config->root.'/templates/components/role-cards.php'; ?>
</div></div></section>
<section id="contact" class="public-section contact-section"><div class="public-container contact-grid"><div><p class="eyebrow">Contact</p><h2>Need access or a little help?</h2><p>For account access, forgotten passwords or questions about your inventory workspace:</p>
<?php if($config->contactEmail!==''): ?><a class="contact-address" href="mailto:<?= Ui::escape($config->contactEmail) ?>"><?= Ui::escape($config->contactEmail) ?></a><?php else: ?><p class="contact-address">Contact your business administrator.</p><?php endif; ?>
</div><div class="contact-note"><span class="public-icon"><?= $ui->icon('user') ?></span><h3><?= Ui::escape($config->businessName) ?></h3><p>Accounts are created by your administrator. There is no public registration.</p></div></div></section>
<section class="bottom-cta"><div class="public-container"><p class="eyebrow">Your next working day, organized</p><h2>Start with a clear view of your stock.</h2><a class="public-button" href="<?= $action ?>"><?= Ui::escape($cta) ?> <?= $ui->icon('arrow') ?></a></div></section>
</main>
<footer class="public-footer"><div class="public-container footer-inner"><a class="brand" href="<?= $link('/') ?>"><span class="brand-mark"><?= $ui->icon('box') ?></span>BizStock</a><p>Inventory management for <?= Ui::escape($config->businessName) ?>.</p><a href="<?= $link('/') ?>#contact">Contact your administrator</a></div></footer>
</body></html>
