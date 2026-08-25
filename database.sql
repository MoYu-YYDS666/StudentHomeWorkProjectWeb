-- ============================================================
-- 初高中作业大赏 - 数据库建表脚本
-- 文件：database.sql
-- 要求：MySQL 5.6+ / InnoDB / utf8mb4
-- 说明：请先手动创建数据库（如 homework_gallery，字符集 utf8mb4），
--       再导入本文件；默认管理员账号推荐由 install.php 自动创建。
-- ============================================================

SET NAMES utf8mb4;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `email` VARCHAR(100) NOT NULL COMMENT '邮箱',
  `password_hash` VARCHAR(255) NOT NULL COMMENT '密码哈希',
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色：user / admin',
  `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '状态：0未验证 1正常 2禁用',
  `email_token` VARCHAR(64) DEFAULT NULL COMMENT '邮箱验证 Token',
  `email_token_expires` DATETIME DEFAULT NULL COMMENT 'Token 过期时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(45) DEFAULT NULL COMMENT '最后登录 IP',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 作业表
CREATE TABLE IF NOT EXISTS `homeworks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '上传用户 ID',
  `image_path` VARCHAR(255) NOT NULL COMMENT '原图路径',
  `thumb_path` VARCHAR(255) NOT NULL COMMENT '缩略图路径',
  `book_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '本数',
  `page_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '页数',
  `description` VARCHAR(500) DEFAULT NULL COMMENT '描述',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1公开 0隐藏',
  `audit_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '审核状态：0待审核 1审核通过 2已拒绝',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '软删除：0正常 1已删除',
  `last_edited_at` DATETIME DEFAULT NULL COMMENT '最近编辑时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status_deleted` (`status`,`is_deleted`),
  CONSTRAINT `fk_homeworks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作业表';

-- ============================================================
-- 默认管理员账号（admin / admin123）
-- 推荐直接访问 install.php 自动创建（自动使用 password_hash 加密）。
-- 如需手动导入，请先用 PHP 生成密码哈希并替换下方占位符：
--   php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
--
-- INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `status`)
-- VALUES ('admin', 'admin@example.com', '此处替换为password_hash生成的哈希', 'admin', 1);
-- ============================================================
