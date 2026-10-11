<?php declare(strict_types=1); use BizStock\Support\Ui; ?>
<section class="panel image-editor <?= Ui::escape($imageClass??'') ?>" data-image-editor="<?= Ui::escape($imageEndpoint) ?>">
<h2><?= $imageEndpoint==='/account/photo'?'Profile photo':'Product image' ?></h2>
<div class="image-preview"><img data-image-preview alt="<?= $imageEndpoint==='/account/photo'?'Your profile photo':'Product image preview' ?>" hidden><span data-image-placeholder>No image</span></div>
<div class="selected-image" data-selected-image hidden><p>Selected image · not saved yet</p><img alt="Selected image preview" data-selected-preview><button class="bs-button bs-button--secondary" type="button" data-image-cancel>Cancel selection</button></div>
<form data-image-form><label>Choose JPEG, PNG or WebP<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label><p class="muted">Up to 2 MiB and 2048 × 2048 pixels. Photos are processed for safe display.</p><div class="form-actions"><button class="bs-button bs-button--primary" type="submit">Upload image</button><button class="bs-button bs-button--secondary" type="button" data-image-remove disabled>Remove image</button></div></form>
<p data-image-message role="status" tabindex="-1" hidden></p></section>
