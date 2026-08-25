<?php
/**
 * ============================================================
 * 初高中作业大赏 - 全局函数库
 * 文件：includes/functions.php
 * 说明：提供 URL 拼接、跳转、登录状态、CSRF、Flash 消息、
 *       输出转义、日期格式化、客户端 IP 获取等通用函数。
 * ============================================================
 */

/**
 * 拼接 BASE_URL 与路径
 * @param string $path 相对路径，如 login.php 或 user/dashboard.php
 * @return string 完整 URL
 */
function base_url($path = '')
{
    return rtrim(BASE_URL, '/') . '/' . ltrim((string)$path, '/');
}

/**
 * 跳转并终止脚本
 * @param string $url 目标地址
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * 是否已登录
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * 获取当前登录用户（ID、用户名、角色），未登录返回 null
 */
function current_user()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => (string)$_SESSION['username'],
        'role'     => (string)$_SESSION['role'],
    ];
}

/**
 * 登录检查：未登录跳转到登录页
 */
function require_login()
{
    if (!is_logged_in()) {
        set_flash('warning', '请先登录后再访问该页面');
        redirect(base_url('login.php'));
    }
}

/**
 * 管理员检查：未登录或非管理员跳转到登录页
 */
function require_admin()
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        set_flash('danger', '无权访问该页面，请以管理员身份登录');
        redirect(base_url('login.php'));
    }
}

/**
 * 获取或生成 CSRF Token（存入 Session）
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 输出隐藏的 CSRF Token 表单字段
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * 验证 POST 提交的 CSRF Token，不匹配时抛出异常
 */
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('页面已过期，请刷新后重试');
    }
    return true;
}

/**
 * 设置 Flash 消息
 * @param string $type success / danger / warning / info
 * @param string $message 消息内容
 */
function set_flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * 输出所有 Flash 消息（Bootstrap 样式），输出后清除
 */
function display_flash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $allowedTypes = ['success', 'danger', 'warning', 'info'];
    foreach ($_SESSION['flash'] as $flash) {
        $type = in_array($flash['type'], $allowedTypes, true) ? $flash['type'] : 'info';
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show flash-message" role="alert">'
            . e($flash['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>'
            . '</div>';
    }
    unset($_SESSION['flash']);
}

/**
 * HTML 转义输出
 */
function e($string)
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * 格式化日期时间为 Y-m-d H:i
 */
function format_datetime($datetime)
{
    if (empty($datetime)) {
        return '';
    }
    $timestamp = strtotime($datetime);
    return $timestamp ? date('Y-m-d H:i', $timestamp) : '';
}

/**
 * 获取客户端真实 IP（考虑常见代理头）
 */
function get_client_ip()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'] as $key) {
        if (!empty($_SERVER[$key])) {
            $first = trim(explode(',', (string)$_SERVER[$key])[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                $ip = $first;
                break;
            }
        }
    }
    return $ip;
}