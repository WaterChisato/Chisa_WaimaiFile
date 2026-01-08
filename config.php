<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', '');       // 数据库名称
define('DB_USER', '');       // 数据库账号
define('DB_PASS', '');        // 数据库密码
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR', __DIR__ . '/uploads'); // 文件存储目录
define('EXPIRE_SECONDS', 3 * 60 * 60); // 默认有效期：3小时

// 上传限制
$ALLOWED_EXTS = ['zip']; // 只允许 zip
$BLOCKED_EXTS = ['py','jar','bat','dat','xml','exe','sh','cmd','com','msi'];
$MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

// PDO连接
function db() {
    static $pdo;
    if ($pdo) return $pdo;
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    ensureSchema($pdo);
    return $pdo;
}

// 自动建表
function ensureSchema(PDO $pdo) {
    // 文件表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS files (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(16) NOT NULL UNIQUE,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime VARCHAR(127) DEFAULT NULL,
            size BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            downloaded TINYINT(1) DEFAULT 0,
            INDEX (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 管理员表 phpct_admin
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS phpct_admin (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 初始化管理员账号
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM phpct_admin");
    $count = $stmt->fetch()['c'];
    if ($count == 0) {
        $defaultUser = 'admin';
        $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO phpct_admin (username, password_hash) VALUES (?, ?)")
            ->execute([$defaultUser, $defaultPass]);
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
}

// 生成唯一取码
function generateCode(PDO $pdo, $len = 8) {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $stmt = $pdo->prepare("SELECT id FROM files WHERE code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetch();
    } while ($exists);
    return $code;
}

// 清理过期文件
function purgeExpired(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT id, stored_name FROM files WHERE expires_at <= NOW()");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {
        $path = UPLOAD_DIR . '/' . $r['stored_name'];
        if (is_file($path)) @unlink($path);
    }
    $pdo->exec("DELETE FROM files WHERE expires_at <= NOW()");
}