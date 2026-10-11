<?php
declare(strict_types=1);
use BizStock\Support\Ui;
$type = in_array($type ?? 'text', ['text', 'password', 'number', 'search', 'email', 'date', 'select', 'textarea'], true) ? ($type ?? 'text') : 'text';
?>
<div class="field">
<label for="<?= Ui::escape($id) ?>"><?= Ui::escape($label) ?><?= !empty($required) ? ' <span class="required-label">(required)</span>' : '' ?></label>
<?php if ($type === 'select'): ?>
<select class="form-select" id="<?= Ui::escape($id) ?>" name="<?= Ui::escape($id) ?>" <?= !empty($required) ? 'required' : '' ?> aria-describedby="<?= Ui::escape($id) ?>-hint <?= Ui::escape($id) ?>-error">
<?php foreach ($options as $key => $option): ?><option value="<?= Ui::escape($key) ?>" <?= (string) ($value ?? '') === (string) $key ? 'selected' : '' ?>><?= Ui::escape($option) ?></option><?php endforeach; ?>
</select>
<?php elseif ($type === 'textarea'): ?>
<textarea class="form-control" id="<?= Ui::escape($id) ?>" name="<?= Ui::escape($id) ?>" rows="3" maxlength="2000" aria-describedby="<?= Ui::escape($id) ?>-hint <?= Ui::escape($id) ?>-error"><?= Ui::escape($value ?? '') ?></textarea>
<?php else: ?>
<?php if($type==='password'): ?><div class="password-control"><?php endif; ?>
<input class="form-control" id="<?= Ui::escape($id) ?>" name="<?= Ui::escape($id) ?>" type="<?= Ui::escape($type) ?>" value="<?= Ui::escape($value ?? '') ?>" <?= !empty($required) ? 'required' : '' ?> <?= !empty($readonly) ? 'readonly' : '' ?> <?= isset($autocomplete) ? 'autocomplete="' . Ui::escape($autocomplete) . '"' : '' ?> <?= isset($maxlength) ? 'maxlength="' . (int) $maxlength . '"' : '' ?> <?= isset($pattern) ? 'pattern="' . Ui::escape($pattern) . '"' : '' ?> <?= $type === 'number' ? 'min="' . Ui::escape($min??'0') . '" max="' . Ui::escape($max ?? '2147483647') . '" step="' . Ui::escape($step ?? '1') . '"' : '' ?> aria-describedby="<?= Ui::escape($id) ?>-hint <?= Ui::escape($id) ?>-error">
<?php endif; ?>
<?php if($type==='password'): ?><button type="button" data-password-toggle data-password-label="<?= Ui::escape(strtolower($label)) ?>" aria-controls="<?= Ui::escape($id) ?>" aria-label="Show <?= Ui::escape(strtolower($label)) ?>" aria-pressed="false">Show</button></div><?php endif; ?>
<p class="field-hint" id="<?= Ui::escape($id) ?>-hint"><?= Ui::escape($hint ?? '') ?></p>
<p class="field-error" id="<?= Ui::escape($id) ?>-error" hidden></p>
</div>
