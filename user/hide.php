<?php
/**
 * ============================================================
 * 初高中作业大赏 - 隐藏 / 显示作业
 * 文件：user/hide.php
 * 说明：仅接受 POST 请求（带 CSRF Token），仅本人或管理员
 *       可操作，切换作业 status（0 隐藏 / 1 公开）。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('danger', '非法请求');
    redirect(base_url('user/manage.php'));
}

try {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('作业不存在');
    }

    $newStatus = Homework::toggleStatus($id, $user['id']);
    set_flash('success', $newStatus === 1 ? '作业已设为公开' : '作业已隐藏');
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('user/manage.php'));