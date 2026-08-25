<?php
/**
 * ============================================================
 * 初高中作业大赏 - 用户禁用 / 启用
 * 文件：admin/user_toggle.php
 * 说明：仅管理员可访问，仅接受 POST（带 CSRF Token），
 *       禁止操作自己的账号。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('danger', '非法请求');
    redirect(base_url('admin/users.php'));
}

$me = current_user();

try {
    verify_csrf();

    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new Exception('用户不存在');
    }
    if ($userId === $me['id']) {
        throw new Exception('不能操作自己的账号');
    }

    $newStatus = User::toggleStatus($userId);
    set_flash('success', '用户状态已更新（当前状态：' . ($newStatus === 1 ? '正常' : '禁用') . '）');
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('admin/users.php'));