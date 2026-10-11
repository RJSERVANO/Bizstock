<?php
declare(strict_types=1);
use BizStock\Support\Ui;
$labels = ['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'];
$status = isset($labels[$status ?? '']) ? $status : 'in';
?>
<span class="stock-badge stock-badge--<?= $status ?>"><?= Ui::escape($labels[$status]) ?></span>
