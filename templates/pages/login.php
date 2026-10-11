<?php declare(strict_types=1); ?>
<section class="panel login-card" aria-labelledby="login-title">
<h1 id="login-title">Welcome back</h1><p>Sign in to manage your business inventory.</p>
<?php if ($state !== 'populated'): ?>
<?= $ui->component('notice', ['tone' => $state === 'error' ? 'error' : 'info', 'heading' => $state === 'error' ? 'Unable to sign in' : 'Sign-in preview', 'message' => $state === 'error' ? 'Check your username and password and try again.' : 'This is a fixture state; authentication is not implemented.']) ?>
<?php endif; ?>
<form data-preview-form="login" novalidate>
<div data-form-errors tabindex="-1" role="alert" hidden></div>
<?= $ui->component('field', ['id' => 'username', 'label' => 'Username', 'required' => true, 'autocomplete' => 'username', 'maxlength' => 50]) ?>
<?= $ui->component('field', ['id' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'maxlength' => 255]) ?>
<?= $ui->component('button', ['label' => $state === 'loading' ? 'Signing in…' : 'Sign in', 'type' => 'submit', 'busy' => $state === 'loading', 'disabled' => $state === 'restricted']) ?>
<p class="mb-0">Accounts are provided by your business administrator.</p>
</form></section>
<?= $ui->component('dialog', ['id' => 'login-review', 'title' => 'Sign-in interface preview', 'message' => 'The form is ready for authentication in Stage 6. No password was sent, saved or logged; no session was created.']) ?>
