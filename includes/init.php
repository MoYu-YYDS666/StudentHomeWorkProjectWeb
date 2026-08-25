<?php
/**
 * ============================================================
 * 初高中作业大赏 - 全局初始化文件
 * 文件：includes/init.php
 * 说明：所有页面入口统一引入本文件，完成配置加载、错误处理、
 *       时区设置、会话启动、类库加载、数据库连接等初始化工作。
 * ============================================================
 */

// ---------- 1. 加载全局配置 ----------
require_once __DIR__ . '/../config/config.php';

// ---------- 2. 错误处理（按 DEV_MODE 决定是否显示错误） ----------
if (defined('DEV_MODE') && DEV_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ---------- 3. 默认时区 ----------
date_default_timezone_set('Asia/Shanghai');

// ---------- 4. 会话安全配置 ----------
session_name(SESSION_NAME);
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
// HTTPS 环境下启用 Secure Cookie
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- 5. 加载 Composer 自动加载文件（存在时，用于 PHPMailer / 官方 Geetest SDK） ----------
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// ---------- 6. 加载核心类 ----------
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Homework.php';
require_once __DIR__ . '/../classes/Geetest.php';
require_once __DIR__ . '/../classes/Mailer.php';

// ---------- 7. 加载全局函数 ----------
require_once __DIR__ . '/functions.php';

// ---------- 8. 初始化数据库连接（失败时给出友好提示，不暴露敏感路径） ----------
try {
    Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    if (defined('DEV_MODE') && DEV_MODE === true) {
        exit('数据库连接失败：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    exit('网站暂时无法访问，请稍后再试。');
}