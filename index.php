<?php require_once __DIR__ . '/config.php'; $pdo = db(); ?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>临时文件交换</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>临时文件交换（3小时有效）</h1>

  <div class="card">
    <h2>上传文件（A）</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
      <label class="label">选择文件</label>
      <input type="file" name="file" required>
      <button type="submit">上传并生成取码</button>
      <p class="tip">⚠️ 仅支持 ZIP 压缩包，大小 ≤50MB。取码分享给 B 即可下载。</p>
    </form>
  </div>

  <div class="card">
    <h2>输入取码下载（B）</h2>
    <form action="download.php" method="get">
      <label class="label">取码</label>
      <input type="text" name="code" maxlength="16" placeholder="如：8位大写字母数字" required>
      <button type="submit">下载文件</button>
      <p class="tip">取码区分大小写，过期后不可下载。</p>
    </form>
  </div>

  <div class="muted">
    <p>文件到期自动清理。请勿上传敏感或违法内容。</p>
  </div>
</div>
</body>
</html>