<?php
// 可被 cron 定时执行，也可手动访问
require_once __DIR__ . '/config.php';
$pdo = db();
purgeExpired($pdo);

// 如果是浏览器访问，输出简单提示
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Cleanup done at " . date('Y-m-d H:i:s') . "\n";
}