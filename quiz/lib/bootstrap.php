<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'Quiz\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    if (str_starts_with($relative, 'Lib\\')) {
        $relative = substr($relative, 4);
    }

    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

session_start();
