

# 📦 文件外卖柜

一个轻量级的文件临时存储与分享系统，灵感来自“外卖柜”。用户可以上传文件，系统生成取码，凭取码下载文件。下载后文件会在短时间内自动删除，保证安全与简洁。

---

## ✨ 功能特性

- **文件上传**：支持最大 1GB 文件，自动生成取码。
- **取码下载**：用户输入取码即可下载文件。
- **自动删除**：文件下载后 30 秒自动删除，或到期自动清理。
- **后台管理**：
  - 文件列表展示（取码、文件名、大小、创建/过期时间、下载状态）
  - 修改管理员账号密码
  - 设置文件有效期（最多 30 天）
  - 一键清除过期文件
  - 删除所有文件（三重确认）
- **安全性**：支持黑名单扩展名过滤，避免上传危险文件。

---

## ⚙️ 安装部署

1. 克隆项目到服务器：
   ```bash
   git clone https://github.com/WaterChisato/Chisa_WaimaiFile.git
   cd Chisa_WaimaiFile

### 配置 PHP 环境：

  - PHP ≥ 7.4

  - MySQL ≥ 5.7

### 修改 php.ini：

- upload_max_filesize = 1024M
- post_max_size = 1024M
- memory_limit = 1024M

### 如果使用 Nginx：

client_max_body_size 1024M;

## 配置数据库：
~~~
导入 schema.sql 建表

管理员表：phpct_admin

文件表：files

修改 config.php：

define('UPLOAD_DIR', __DIR__ . '/uploads');
define('EXPIRE_SECONDS', 10800); // 默认3小时
$MAX_FILE_SIZE = 1024 * 1024 * 1024; // 1GB
~~~
## 🚀 使用方式
~~~
用户入口：访问 index.php

上传文件 → 获取取码

输入取码 → 下载文件

管理员入口：访问 login-admin.php

登录后进入 admin.php

管理文件、账号、系统维护
~~~
---
### ✍️修改配置
在config.php修改配置
~~~
define('DB_HOST', 'localhost');
define('DB_NAME', '');       // 数据库名称
define('DB_USER', '');       // 数据库账号
define('DB_PASS', '');        // 数据库密码
~~~
---
## 📂 项目结构
~~~
file-waimai/
├── index.php              # 用户首页
├── upload.php             # 文件上传逻辑
├── download.php           # 文件下载逻辑
├── admin.php              # 管理员后台
├── login-admin            # 管理员登录
├── config.php             # 配置文件
├── styles.css             # 样式文件
├── uploads/               # 文件存储目录
└── README.md              # 项目说明
~~~
### 🛡️ 注意事项
~~~
上传文件最大 1GB，超过会提示错误。

下载后文件将在 30 秒后自动删除。

管理员操作需谨慎，尤其是“删除所有文件”功能（三重确认）。

建议定期执行“一键清除过期文件”，保持系统整洁。
~~~
