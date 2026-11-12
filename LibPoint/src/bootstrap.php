<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

// Simple class autoloader: one class per file, file name = class name.
spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'pages', 'pages/admin'] as $dir) {
        $file = BASE_PATH . "/src/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});

Session::start();
