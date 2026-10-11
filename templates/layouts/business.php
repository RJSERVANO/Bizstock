<?php declare(strict_types=1);use BizStock\Support\Ui;$admin=$user['role']==='Admin';$viewer=$user['role']==='Viewer'; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="app-base-path" content="<?= Ui::escape($config->basePath) ?>"><title><?= Ui::escape($title) ?> · BizStock</title>
<?php foreach(['vendor/bootstrap.min.css','css/tokens.css','css/components.css','css/layout.css','css/usability.css'] as $asset): ?><link rel="stylesheet" href="<?= Ui::escape($ui->asset($asset)) ?>"><?php endforeach; ?>
<?php foreach(['components.js','api-client.js','business.js','pages/'.($screen==='account'?'auth':$screen).'.js'] as $asset): ?><script src="<?= Ui::escape($ui->asset('js/'.$asset)) ?>" defer></script><?php endforeach; ?>
<?php if($screen==='account'): ?><script src="<?= Ui::escape($ui->asset('js/pages/account.js')) ?>" defer></script><?php endif; ?>
</head><body><?php require $config->root.'/templates/components/workspace-start.php'; ?>
<main class="content" id="main-content" tabindex="-1" data-screen="<?= Ui::escape($screen) ?>" data-role="<?= Ui::escape($user['role']) ?>" data-product-id="<?= $id??'' ?>"><div class="page-heading"><div><h1><?= Ui::escape($title) ?></h1><p><?= Ui::escape(match($screen){'dashboard'=>'A clear view of your inventory today.','products'=>'Keep your catalogue and stock levels organized.','movements'=>'Receive stock, issue items or correct a previous movement.','history'=>'Review every stock movement and correction.','users'=>'Manage your team and their access.','audit-events'=>'Track changes made in your workspace.','account'=>'Manage your password and account security.',default=>'Manage your business inventory.'}) ?></p></div><?php if($screen==='products'&&$admin): ?><a class="bs-button bs-button--primary" href="<?= Ui::escape($config->url('/products/new')) ?>">Add product</a><?php endif; ?></div>
<div data-message class="notice" role="status" tabindex="-1" hidden></div>
<?php require $config->root.'/templates/live/'.$screen.'.php'; ?>
</main></div></div><div id="ui-announcement" class="visually-hidden" role="status" aria-live="polite"></div>
<?= $ui->component('dialog',['id'=>'unsaved-review','title'=>'Leave this form?','message'=>'Your unsaved changes will be discarded.']) ?>
<?= $ui->component('dialog',['id'=>'action-review','title'=>'Confirm action','message'=>'Review the details before continuing.','confirmLabel'=>'Confirm']) ?>
<?= $ui->component('dialog',['id'=>'detail-review','title'=>'Record details','message'=>'Values are loaded from the server.']) ?>
<script src="<?= Ui::escape($ui->asset('js/images.js')) ?>" defer></script></body></html>
