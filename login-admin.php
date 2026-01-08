<?php
require_once __DIR__ . '/config.php';
session_start();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM phpct_admin WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $admin['username'];
        header("Location: wh-gz-admin.php");
        exit;
    } else {
        $error = "用户名或密码错误";
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>管理员登录</title>
<link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <div class="card">
    <h2>管理员登录</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
      <label class="label">用户名</label>
      <input type="text" name="username" required>
      <label class="label">密码</label>
      <input type="password" name="password" required>
      <button type="submit">登录</button>
    </form>
  </div>
</div>
</body>
</html>