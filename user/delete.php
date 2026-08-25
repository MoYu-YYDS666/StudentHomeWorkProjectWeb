<?php
/**
 * ============================================================
 * 初高中作业大赏 - 删除作业（软删除）
 * 文件：user/delete.php
 * 说明：仅接受 POST 请求（带 CSRF Token），仅本人或管理员
 *       可操作，执行软删除（is_deleted=1，不删除图片文件）。
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

    Homework::softDelete($id, $user['id']);
    set_flash('success', '删除成功');
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('user/manage.php'));