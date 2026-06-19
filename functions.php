<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Gradify\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once __DIR__ . '/app/theme.php';

use Gradify\Theme;

const THEME_INDEX_FILE = __FILE__;
const THEME_INDEX_DIR = __DIR__;

Theme::register();
