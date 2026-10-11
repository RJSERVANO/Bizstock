<?php
declare(strict_types=1);
/** @var \BizStock\Support\Config $config */
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BizStock — Foundation</title>
    <link rel="stylesheet" href="<?= $escape($config->url('/assets/vendor/bootstrap.min.css')) ?>">
</head>
<body>
<main class="container py-5">
    <h1>BizStock</h1>
    <p class="lead">The application foundation is running.</p>
    <p>Sign in to view inventory, record stock movements and run reports with your permitted access.</p>
    <p><a href="<?= $escape($config->url('/login')) ?>">Sign in</a> · <a href="<?= $escape($config->url('/account')) ?>">Your account</a></p>
    <a href="<?= $escape($config->url('/api/v1/health')) ?>">Check application liveness</a>
    <?php if ($config->environment !== 'production'): ?>
    <p class="mt-3"><a href="<?= $escape($config->url('/preview/dashboard')) ?>">Open Stage 3 fixture screens</a></p>
    <?php endif; ?>
</main>
</body>
</html>
