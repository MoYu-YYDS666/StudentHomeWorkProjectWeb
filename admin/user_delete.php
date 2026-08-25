<?php
/**
 * ============================================================
 * 初高中作业大赏 - 删除用户
 * 文件：admin/user_delete.php
 * 说明：仅管理员可访问，仅接受 POST（带 CSRF Token），
 *       物理删除用户（级联删除其作业及图片文件），禁止删除自己。
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
        throw new Exception('不能删除自己的账号');
    }

    User::deleteUser($userId);
    set_flash('success', '用户及其全部作业已删除');
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('admin/users.php'));