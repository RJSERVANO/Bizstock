<?php declare(strict_types=1); use BizStock\Support\Ui; ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="app-base-path" content="<?= Ui::escape($config->basePath) ?>">
<title><?= $screen==='login'?'Sign in':'Account' ?> · BizStock</title>
<?php foreach(['vendor/bootstrap.min.css','css/tokens.css','css/components.css','css/layout.css','css/public.css','css/usability.css'] as $asset): ?><link rel="stylesheet" href="<?= Ui::escape($ui->asset($asset)) ?>"><?php endforeach; ?>
<script src="<?= Ui::escape($ui->asset('js/components.js')) ?>" defer></script>
<script src="<?= Ui::escape($ui->asset('js/api-client.js')) ?>" defer></script>
<script src="<?= Ui::escape($ui->asset('js/pages/auth.js')) ?>" defer></script>
</head><body class="<?= $screen==='login'?'auth-public':'' ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<?php if($screen==='login'): ?><header class="login-header"><div><a class="brand" href="<?= Ui::escape($config->url('/')) ?>"><span class="brand-mark"><?= $ui->icon('box') ?></span>BizStock</a><a href="<?= Ui::escape($config->url('/')) ?>">Back to home</a></div></header><?php endif; ?>
<main id="main-content" class="login-page" tabindex="-1"><div class="login-wrap">
<?php if($screen==='account'): ?><a class="brand" href="<?= Ui::escape($config->url('/')) ?>"><span class="brand-mark"><?= $ui->icon('box') ?></span>BizStock</a><?php endif; ?>
<section class="panel login-card" aria-labelledby="auth-title">
<h1 id="auth-title"><?= $screen==='login'?'Welcome back':'Your account' ?></h1>
<?php if($user): ?>
<p><?= Ui::escape($user['full_name']) ?> · <?= Ui::escape($user['username']) ?> · <?= Ui::escape($user['role']) ?></p>

<?php if($user['must_change_password']): ?><div data-forced-notice><?= $ui->component('notice',['tone'=>'info','heading'=>'Change your initial password','message'=>'Set a new password before accessing inventory.']) ?></div><?php endif; ?>
<?php else: ?><p>Sign in to access your business inventory.</p><?php endif; ?>
<noscript><p>Enable JavaScript to securely submit this form.</p></noscript>
<form data-auth-form="<?= Ui::escape($screen) ?>">
<div data-form-errors tabindex="-1" role="alert" hidden></div>
<p data-form-status role="status" hidden></p>
<?php if($screen==='login'): ?>
<fieldset class="role-picker"><legend>Choose a role for sign-in guidance</legend><div class="role-picker-controls"><?php foreach(['Admin','Staff','Viewer'] as $role): ?><button type="button" class="bs-button bs-button--secondary" data-role-hint="<?= $role ?>" aria-pressed="<?= $roleHint===$role?'true':'false' ?>"><?= $role ?></button><?php endforeach; ?></div><p id="role-guidance" role="status">Use your own administrator-provided credentials. Your account determines your access.</p></fieldset>
<?= $ui->component('field',['id'=>'username','label'=>'Username','required'=>true,'autocomplete'=>'username','maxlength'=>50]) ?>

<?= $ui->component('field',['id'=>'password','label'=>'Password','type'=>'password','required'=>true,'autocomplete'=>'current-password','maxlength'=>72]) ?>
<?php else: ?>
<?= $ui->component('field',['id'=>'current_password','label'=>'Current password','type'=>'password','required'=>true,'autocomplete'=>'current-password','maxlength'=>72]) ?>
<?= $ui->component('field',['id'=>'new_password','label'=>'New password','type'=>'password','required'=>true,'autocomplete'=>'new-password','maxlength'=>72,'hint'=>'Use at least 12 characters. Choose a different password.']) ?>
<?= $ui->component('field',['id'=>'confirm_password','label'=>'Confirm new password','type'=>'password','required'=>true,'autocomplete'=>'new-password','maxlength'=>72]) ?>
<?php endif; ?>
<?= $ui->component('button',['label'=>$screen==='login'?'Sign in':'Change password','type'=>'submit']) ?>
<?php if($screen==='login'): ?><p class="login-help">Forgotten your password? Contact your business administrator. Initial passwords may have been changed already.</p><?php endif; ?>
</form>
<?php if($screen==='account'): ?><div data-auth-logout><?= $ui->component('button',['label'=>'Sign out','variant'=>'secondary']) ?></div><?php endif; ?>
</section></div>
<?php if($screen==='login'): ?><aside class="login-visual" aria-labelledby="role-signin-title"><div class="login-visual-copy"><h2 id="role-signin-title">Your inventory.<br>Under control.</h2><p>Track stock, manage your team and keep your business moving.</p></div><img src="<?= Ui::escape($ui->asset('images/login-inventory-960.webp')) ?>" srcset="<?= Ui::escape($ui->asset('images/login-inventory-480.webp')) ?> 480w, <?= Ui::escape($ui->asset('images/login-inventory-960.webp')) ?> 960w, <?= Ui::escape($ui->asset('images/login-inventory-1280.webp')) ?> 1280w" sizes="(max-width:900px) 100vw, 600px" width="1536" height="1024" alt="Organized shelves and cartons with a barcode scanner and stock tablet" data-login-illustration><p class="login-image-fallback" data-login-fallback hidden>Organized inventory, ready for your next business day.</p></aside><?php endif; ?>
</main><?php if($screen==='login'): ?><footer class="login-footer">BizStock <span aria-hidden="true">&middot;</span> Your business inventory workspace</footer><?php endif; ?></body></html>
