<?php
/**
 * ============================================================
 * 初高中作业大赏 - 退出登录
 * 文件：logout.php
 * 说明：清空会话数据、销毁 Session，并重定向到首页。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    // 防止会话固定：先重新生成会话 ID 再销毁
    session_regenerate_id(true);

    // 清空会话数据
    $_SESSION = [];

    // 删除会话 Cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

redirect(base_url('index.php'));