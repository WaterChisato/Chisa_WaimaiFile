<?php
require_once __DIR__ . '/config.php';
session_start();
$pdo = db();

// 未登录则跳转到登录界面
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login-admin.php");
    exit;
}

// 修改管理员账号密码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $newUser = trim($_POST['new_user']);
    $newPass = trim($_POST['new_pass']);
    if ($newUser !== '' && $newPass !== '') {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE phpct_admin SET username = ?, password_hash = ? WHERE username = ?");
        $stmt->execute([$newUser, $hash, $_SESSION['admin_user']]);
        $_SESSION['admin_user'] = $newUser;
        $msg = "管理员信息已更新";
    } else {
        $msg = "用户名或密码不能为空";
    }
}

// 删除文件
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("SELECT stored_name FROM files WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $path = UPLOAD_DIR . '/' . $row['stored_name'];
        if (is_file($path)) @unlink($path);
        $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$id]);
    }
    header("Location: wh-gz-admin.php");
    exit;
}

// 下载文件（无需取码）
if (isset($_GET['download'])) {
    $id = intval($_GET['download']);
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $path = UPLOAD_DIR . '/' . $row['stored_name'];
        if (is_file($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($row['mime'] ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . rawurlencode($row['original_name']) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
    }
    echo "文件不存在或已删除";
    exit;
}

// 设置文件有效期（限制最大30天）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_expire'])) {
    $id = intval($_POST['file_id']);
    $seconds = intval($_POST['expire_seconds']);
    if ($seconds > 0 && $seconds <= 2592000) {
        $expiresAt = date('Y-m-d H:i:s', time() + $seconds);
        $stmt = $pdo->prepare("UPDATE files SET expires_at = ? WHERE id = ?");
        $stmt->execute([$expiresAt, $id]);
        $msg = "文件有效期已更新";
    } else {
        $msg = "请输入1~2592000之间的秒数（最多30天）";
    }
}

// 一键清除过期文件
if (isset($_POST['purge_expired'])) {
    purgeExpired($pdo);
    $msg = "已清除所有过期文件";
}

// 获取所有文件
$stmt = $pdo->query("SELECT * FROM files ORDER BY created_at DESC");
$files = $stmt->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>管理员面板</title>
<link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <h1>管理员面板</h1>
  <?php if (!empty($msg)) echo "<p style='color:green;'>$msg</p>"; ?>

  <!-- 修改管理员账号 -->
  <div class="card">
    <h2>修改管理员账号</h2>
    <form method="post">
      <input type="hidden" name="update_admin" value="1">
      <label class="label">新用户名</label>
      <input type="text" name="new_user" required>
      <label class="label">新密码</label>
      <input type="password" name="new_pass" required>
      <button type="submit">更新</button>
    </form>
  </div>

  <!-- 文件列表 -->
  <div class="card">
    <h2>文件列表</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
      <tr>
        <th>ID</th>
        <th>取码</th>
        <th>文件名</th>
        <th>大小</th>
        <th>创建时间</th>
        <th>过期时间</th>
        <th>已下载</th>
        <th>操作</th>
      </tr>
      <?php if (count($files) > 0): ?>
        <?php foreach ($files as $f): ?>
        <tr>
          <td><?php echo $f['id']; ?></td>
          <td><?php echo htmlspecialchars($f['code']); ?></td>
          <td><?php echo htmlspecialchars($f['original_name']); ?></td>
          <td><?php echo round($f['size']/1024/1024,2); ?> MB</td>
          <td><?php echo $f['created_at']; ?></td>
          <td><?php echo $f['expires_at']; ?></td>
          <td><?php echo $f['downloaded'] ? "是" : "否"; ?></td>
          <td>
            <a href="wh-gz-admin.php?download=<?php echo $f['id']; ?>">下载</a> |
            <a href="wh-gz-admin.php?delete=<?php echo $f['id']; ?>" onclick="return confirm('确认删除?')">删除</a>
            <form method="post" style="display:inline;">
              <input type="hidden" name="set_expire" value="1">
              <input type="hidden" name="file_id" value="<?php echo $f['id']; ?>">
              <input type="number" name="expire_seconds" placeholder="秒数" style="width:80px;">
              <button type="submit">设置有效期</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">暂无文件</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- 系统维护 -->
  <div class="card">
    <h2>系统维护</h2>
    <form method="post">
      <input type="hidden" name="purge_expired" value="1">
      <button type="submit" onclick="return confirm('确认清除所有过期文件?')">一键清除过期文件</button>
    </form>
  </div>
</div>
</body>
</html>