<?php
/**
 * ============================================================
 * 初高中作业大赏 - 作业隐藏 / 显示（管理员）
 * 文件：admin/homework_toggle.php
 * 说明：仅管理员可访问，仅接受 POST（带 CSRF Token），
 *       切换作业 status（0 隐藏 / 1 公开）。
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

    $newStatus = Homework::toggleStatus($homeworkId, $me['id']);
    set_flash('success', $newStatus === 1 ? '作业已设为公开' : '作业已隐藏');
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('admin/homeworks.php'));