<?php
/**
 * ============================================================
 * 初高中作业大赏 - 全局配置文件
 * 文件：config/config.php
 * 说明：站点运行所需的全部配置常量，部署时按实际环境修改。
 * ============================================================
 */

/* ==================== 数据库配置 ==================== */
define('DB_HOST', 'localhost');           // 数据库服务器地址（本机一般为 127.0.0.1 或 localhost）
define('DB_NAME', '192_168_1_200');    // 数据库名称，请提前创建好该数据库
define('DB_USER', '192_168_1_200');                // 数据库用户名
define('DB_PASS', 'zm4PrM7ExbNP1YRG');                    // 数据库密码
define('DB_CHARSET', 'utf8mb4');          // 数据库字符集，务必保持 utf8mb4

/* ==================== 站点地址 ==================== */
define('BASE_URL', 'http://192.168.1.200');   // 网站根地址，如 http://localhost 或 https://example.com（不要以 / 结尾）

/* ==================== 图片存储目录 ==================== */
define('UPLOAD_PATH', __DIR__ . '/../uploads/');    // 原图保存目录（相对本文件定位到 uploads/，目录需可写）
define('THUMB_PATH', __DIR__ . '/../thumbnails/');  // 缩略图保存目录（thumbnails/，目录需可写）

/* ==================== Geetest4 滑动验证码 ==================== */
define('GEETEST_ID', '1b2f5eddeba07fe492282c467b65b4cb');                 // Geetest4 验证码 ID（在极验后台获取）
define('GEETEST_KEY', '56e19f1e4439ffb236499eedf45bf4ad');                // Geetest4 验证码 Key（在极验后台获取）

/* ==================== SMTP 邮件配置（PHPMailer） ==================== */
define('SMTP_HOST', 'smtp.qq.com');  // SMTP 服务器地址，如 smtp.qq.com / smtp.163.com
define('SMTP_PORT', 587);                 // SMTP 端口：SSL 加密用 465，TLS 加密用 587
define('SMTP_USERNAME', 'onelovewall@foxmail.com');              // SMTP 登录账号（通常为完整邮箱地址）
define('SMTP_PASSWORD', 'qjcfbpqzkcaydcde');              // SMTP 登录密码或授权码（QQ/163 邮箱需使用授权码）
define('SMTP_ENCRYPTION', 'tls');         // 加密方式：tls 或 ssl，与端口对应
define('SMTP_FROM_EMAIL', 'onelovewall@foxmail.com');            // 发件人邮箱地址
define('SMTP_FROM_NAME', '初高学生作业统计Project'); // 发件人显示名称

/* ==================== 会话（Session）配置 ==================== */
define('SESSION_NAME', 'HOMEWORK_GALLERY_SESSION'); // Session Cookie 名称
define('SESSION_LIFETIME', 7200);         // Session 有效时间（秒），默认 7200 秒 = 2 小时

/* ==================== 调试与安全 ==================== */
define('DEV_MODE', true);                // 调试开关：true 显示详细错误（仅开发环境），false 关闭错误显示（生产环境）
define('MAX_FILE_SIZE', 5242880);         // 上传图片大小上限（字节），默认 5242880 = 5MB
/* ==================== 上传页公告（可选） ==================== */
// 在“上传作业”页面顶部显示一个公告框，支持 HTML 格式，
// 可直接写 <p>、<ul>、<li>、<strong>、<a> 等标签；
// 内容来自本站配置文件，属于可信内容，请勿粘贴用户输入。
// 设为空字符串 '' 则不显示公告框。
define('UPLOAD_NOTICE', '
    <p class="mb-1"><i class="bi bi-megaphone me-1"></i><strong>上传须知</strong></p>
    <ul class="mb-0">
        <li>请上传本人真实作业，图片需清晰可读。</li>
        <li>仅支持 JPG / PNG / GIF / WEBP 格式，大小不超过 5MB。</li>
        <li>作业提交后将由管理员审核，审核通过后才会在画廊公开展示。</li>
    </ul>
');