<?php
require_once __DIR__ . '/config.php';
$pdo = db();

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if ($code === '' || !preg_match('/^[A-Z0-9]{6,16}$/', $code)) {
    renderMsg("取码格式不正确。");
}

// 下载前先清理过期文件
purgeExpired($pdo);

$stmt = $pdo->prepare("SELECT * FROM files WHERE code = ? LIMIT 1");
$stmt->execute([$code]);
$row = $stmt->fetch();

if (!$row) renderMsg("取码不存在或已过期。");
if (strtotime($row['expires_at']) <= time()) renderMsg("文件已过期并删除。");

$path = UPLOAD_DIR . '/' . $row['stored_name'];
if (!is_file($path)) {
    $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$row['id']]);
    renderMsg("文件已不可用。");
}

// 输出下载响应头
header('Content-Description: File Transfer');
header('Content-Type: ' . ($row['mime'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . rawurlencode($row['original_name']) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache');

$fp = fopen($path, 'rb');
if ($fp) {
    fpassthru($fp);
    fclose($fp);

    // 下载完成后，把过期时间改为 30 秒后
    $pdo->prepare("UPDATE files SET expires_at = DATE_ADD(NOW(), INTERVAL 30 SECOND) WHERE id = ?")
        ->execute([$row['id']]);

    exit;
} else {
    renderMsg("读取文件失败。");
}

function renderMsg($msg) {
    header('Content-Type: text/html; charset=utf-8'); ?>
    <!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="styles.css"><title>下载提示</title></head>
    <body><div class="container"><div class="card"><h2>提示</h2><p><?php echo htmlspecialchars($msg); ?></p>
    <p><a href="index.php">返回首页</a></p></div></div></body></html>
    <?php exit;
}