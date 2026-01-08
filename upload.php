<?php
require_once __DIR__ . '/config.php';
$pdo = db();

// 检查是否有文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    exit("上传失败，请重试。");
}

$file = $_FILES['file'];
$originalName = $file['name'];
$sizeBytes = $file['size'];
$mimeType = $file['type'];

// 校验大小
global $MAX_FILE_SIZE;
if ($sizeBytes > $MAX_FILE_SIZE) {
    exit("文件过大，最大允许 " . ($MAX_FILE_SIZE/1024/1024) . " MB");
}

// 校验扩展名
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
global $ALLOWED_EXTS, $BLOCKED_EXTS;
if (!in_array($ext, $ALLOWED_EXTS) || in_array($ext, $BLOCKED_EXTS)) {
    exit("不允许的文件类型。");
}

// 保存文件
$storedName = uniqid("f_", true) . "." . $ext;
$destPath = UPLOAD_DIR . "/" . $storedName;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    exit("保存文件失败。");
}

// 生成取码
$code = generateCode($pdo, 8);

// 计算时间（方案 A：用 PHP 计算）
$now = time();
$expiresTs = $now + EXPIRE_SECONDS; // 默认 3 小时，可在 config.php 修改
$createdAt = date('Y-m-d H:i:s', $now);
$expiresAt = date('Y-m-d H:i:s', $expiresTs);

// 写入数据库
$stmt = $pdo->prepare("
    INSERT INTO files (code, original_name, stored_name, mime, size, created_at, expires_at, downloaded)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0)
");
$stmt->execute([
    $code,
    $originalName,
    $storedName,
    $mimeType,
    $sizeBytes,
    $createdAt,
    $expiresAt
]);

// 输出提示页面
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>上传成功</title>
<link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <div class="card">
    <h2>上传成功</h2>
    <p>取码：<strong><?php echo htmlspecialchars($code); ?></strong></p>
    <p>有效期至：<?php echo $expiresAt; ?></p>
    <p><a href="index.php">返回首页</a></p>
  </div>
</div>
</body></html>