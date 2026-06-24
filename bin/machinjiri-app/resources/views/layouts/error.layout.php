<?php use Mlangeni\Machinjiri\Core\Views\View; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Error <?= $code ?? 500 ?></title>
    <?php View::yield('styles'); ?>
</head>
<body>
    <?php View::yield('content'); ?>
</body>
</html>