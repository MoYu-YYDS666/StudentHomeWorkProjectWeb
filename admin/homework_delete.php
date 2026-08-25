<?php
/**
 * ============================================================
 * 初高中作业大赏 - 删除作业（管理员）
 * 文件：admin/homework_delete.php
 * 说明：仅管理员可访问，仅接受 POST（带 CSRF Token），
 *       执行软删除（is_deleted=1）。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('danger', '非法请求');
    redirect(base_url('admin/homeworks.php'));
}

$me = current_user();

try {
    verify_csrf();

    $homeworkId = (int)($_POST['homework_id'] ?? 0);
    if ($homeworkId <= 0) {
        throw new Exception('作业不存在');
    }

    Homework::softDelete($homeworkId, $me['id']);
    set_flash('success', '删除成功');
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('admin/homeworks.php'));