<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class Resources 
{

    public static function notFoundLayoutTemplate() { return <<<'PHP'
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
PHP;
    }
    
    public static function notFoundViewTemplate() { return <<<'PHP'
<?php use Mlangeni\Machinjiri\Core\Views\View; ?>
<?php View::extend('layouts.error'); ?>

<?php View::section('content'); ?>
<div class="error-container">
    <div class="error-header">
        <div class="error-code">404 - Not Found</div>
    </div>
    <div class="error-body">
        <h1 class="error-title">The page you are looking for could not be found.</h1>
        <div class="error-message">The server returned a 404 error for the requested resource.</div>
        <div class="error-actions"><a href="/" class="btn-primary">Return Home</a></div>
    </div>
</div>
<?php View::endSection(); ?>

<?php section('styles') ?>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #FCF7F0;
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #2E2C2A;
}

.error-container {
    max-width: 650px;
    width: 100%;
    background: #FFFFFFDD;
    backdrop-filter: blur(2px);
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
    border: 1px solid #F2E5D8;
    overflow: hidden;
}

.error-header {
    padding: 30px 30px 0 30px;
    border-bottom: 1px solid #F2E5D8;
}

.error-code {
    font-size: 30px;
    font-weight: 700;
    color: #E68A5E;
    letter-spacing: -1px;
    line-height: 1;
    margin-bottom: 10px;
}

.error-status {
    font-size: 16px;
    font-weight: 500;
    color: #C4633A;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 20px;
}

.error-body {
    padding: 30px;
}

.error-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #2E2C2A;
}

.error-message {
    font-size: 16px;
    line-height: 1.5;
    color: #2E2C2A;
    margin-bottom: 25px;
    background: #FDE8E8;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 3px solid #F5C6C6;
}

.requested-path {
    background: rgba(242, 229, 216, 0.5);
    padding: 12px 16px;
    border-radius: 8px;
    font-family: 'SF Mono', 'Menlo', monospace;
    font-size: 14px;
    word-break: break-all;
    margin-bottom: 25px;
    border: 1px solid #F2E5D8;
}

.requested-path strong {
    color: #E68A5E;
    font-weight: 600;
}

.error-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.error-actions a, .error-actions button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-family: inherit;
}

.btn-primary {
    background: #E68A5E;
    color: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.btn-primary:hover {
    background: #C4633A;
    transform: translateY(-1px);
}

.btn-secondary {
    background: transparent;
    color: #2E2C2A;
    border: 1px solid #F2E5D8;
}

.btn-secondary:hover {
    background: #F2E5D8;
    border-color: #E68A5E;
}

.error-footer {
    padding: 20px 30px;
    background: rgba(242, 229, 216, 0.3);
    border-top: 1px solid #F2E5D8;
    font-size: 13px;
    color: #2E2C2A;
    text-align: center;
}

.error-footer span {
    color: #E68A5E;
}

@media (max-width: 550px) {
    .error-header, .error-body, .error-footer {
        padding-left: 20px;
        padding-right: 20px;
    }
    .error-code {
        font-size: 54px;
    }
    .error-title {
        font-size: 20px;
    }
    .error-actions a, .error-actions button {
        padding: 8px 16px;
        font-size: 13px;
    }
}
</style>
<?php View::endSection() ?>
PHP;
    }
}