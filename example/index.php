<?php
require __DIR__ . '/../vendor/autoload.php';
use CoreEnv\Env;
// Initialize loader pointing to example dir
$env = Env::getInstance(__DIR__ . '/');
echo "App: " . $env->getString('APP_NAME') . PHP_EOL;
echo "Env: " . $env->getString('APP_ENV') . PHP_EOL;
echo "Debug: " . ($env->getBool('APP_DEBUG') ? 'yes' : 'no') . PHP_EOL;
echo "DB host: " . $env->getString('DB_HOST') . ':' . $env->getInt('DB_PORT') . PHP_EOL;
// Example of validation
try {
    $env->requireVars(['DB_HOST','DB_USERNAME','DB_PASSWORD']);
    echo "Required vars present." . PHP_EOL;
} catch (Exception $e) {
    echo "Validation error: " . $e->getMessage() . PHP_EOL;
}
