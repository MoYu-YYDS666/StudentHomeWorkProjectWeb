<?php
/**
 * ============================================================
 * 初高中作业大赏 - 安装程序
 * 文件：install.php
 * 说明：首次部署时运行本文件自动建表、创建默认管理员
 *       （admin / admin123）并创建上传目录。
 * 注意：安装完成后请立即删除本文件（或删除 install.lock 重新安装）。
 * ============================================================
 */
require_once __DIR__ . '/config/config.php';

// 错误处理
if (defined('DEV_MODE') && DEV_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Asia/Shanghai');
require_once __DIR__ . '/classes/Database.php';

$lockFile = __DIR__ . '/install.lock';
$alreadyInstalled = file_exists($lockFile);

$messages = [];
$hasError = false;
$dbError = '';

// 尝试连接并检查是否已安装
$tablesExist = false;
if (!$alreadyInstalled) {
    try {
        $db = Database::getInstance();
        $row = $db->fetch("SHOW TABLES LIKE 'users'");
        $tablesExist = !empty($row);
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

// 执行安装
if (!$alreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getPdo();

        // 1. 创建 users 表
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `users` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `username` VARCHAR(50) NOT NULL,
              `email` VARCHAR(100) NOT NULL,
              `password_hash` VARCHAR(255) NOT NULL,
              `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
              `status` TINYINT(1) NOT NULL DEFAULT 0,
              `email_token` VARCHAR(64) DEFAULT NULL,
              `email_token_expires` DATETIME DEFAULT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `last_login_at` DATETIME DEFAULT NULL,
              `last_login_ip` VARCHAR(45) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_username` (`username`),
              UNIQUE KEY `uk_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $messages[] = 'users 表创建成功';

        // 2. 创建 homeworks 表（含审核状态字段 audit_status）
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `homeworks` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT UNSIGNED NOT NULL,
              `image_path` VARCHAR(255) NOT NULL,
              `thumb_path` VARCHAR(255) NOT NULL,
              `book_count` INT UNSIGNED NOT NULL DEFAULT 0,
              `page_count` INT UNSIGNED NOT NULL DEFAULT 0,
              `description` VARCHAR(500) DEFAULT NULL,
              `status` TINYINT(1) NOT NULL DEFAULT 1,
              `audit_status` TINYINT(1) NOT NULL DEFAULT 0,
              `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
              `last_edited_at` DATETIME DEFAULT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`),
              KEY `idx_status_deleted` (`status`,`is_deleted`),
              CONSTRAINT `fk_homeworks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $messages[] = 'homeworks 表创建成功';

        // 3. 创建默认管理员账号（不存在时）
        $admin = $db->fetch("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
        if (!$admin) {
            $db->execute(
                'INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 1)',
                ['admin', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']
            );
            $messages[] = '默认管理员账号创建成功（admin / admin123）';
        } else {
            $messages[] = '管理员账号已存在，跳过创建';
        }

        // 4. 创建上传目录
        foreach ([UPLOAD_PATH, THUMB_PATH] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            @chmod($dir, 0755);
            $messages[] = '目录已就绪：' . $dir;
        }

        // 5. 写入安装锁文件
        @file_put_contents($lockFile, date('Y-m-d H:i:s'));
        $alreadyInstalled = true;
        $messages[] = '安装锁文件已创建（install.lock）';
    } catch (Exception $e) {
        $hasError = true;
        $messages[] = '安装失败：' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安装 - 初高中作业大赏</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-4"><i class="bi bi-tools"></i> 初高中作业大赏 - 安装程序</h1>

            <?php if ($dbError !== ''): ?>
                <div class="alert alert-danger">
                    数据库连接失败：<?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>
                    <p class="mb-0 mt-2 small">
                        请确认 config/config.php 中的数据库配置正确、数据库已创建、MySQL 服务已启动。
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!empty($messages)): ?>
                <div class="alert <?= $hasError ? 'alert-danger' : 'alert-success' ?>">
                    <ul class="mb-0">
                        <?php foreach ($messages as $message): ?>
                            <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($alreadyInstalled): ?>
                <div class="alert alert-warning">
                    <strong>网站已安装。</strong> 出于安全考虑，请立即删除 <code>install.php</code> 文件
                    （以及 <code>install.lock</code>）。
                </div>
                <p class="mb-0">
                    默认管理员账号：<code>admin</code> / <code>admin123</code>，
                    登录后请尽快修改密码并配置 SMTP 与 Geetest4。
                </p>
            <?php elseif ($tablesExist && $dbError === ''): ?>
                <div class="alert alert-warning">
                    检测到数据库表已存在（可能已通过 database.sql 导入）。
                    点击下方按钮将只创建缺失的管理员账号与目录。
                </div>
            <?php endif; ?>

            <?php if (!$alreadyInstalled && $dbError === ''): ?>
                <form method="post">
                    <input type="hidden" name="install" value="1">
                    <button type="submit" class="btn btn-primary w-100">开始安装</button>
                </form>
                <p class="small text-muted text-center mt-3 mb-0">
                    将自动创建：users 表、homeworks 表、默认管理员（admin / admin123）、uploads/ 与 thumbnails/ 目录。
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>