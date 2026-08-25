<?php
/**
 * ============================================================
 * 初高中作业大赏 - 作业审核（管理员）
 * 文件：admin/homework_audit.php
 * 说明：仅管理员可访问，仅接受 POST（带 CSRF Token）。
 *       audit_action=approve 审核通过（自动设为公开）；
 *       audit_action=reject  审核拒绝（直接删除原图、缩略图与记录）。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('danger', '非法请求');
    redirect(base_url('admin/homeworks.php'));
}

try {
    verify_csrf();

    $homeworkId = (int)($_POST['homework_id'] ?? 0);
    $action = (string)($_POST['audit_action'] ?? '');
    if ($homeworkId <= 0) {
        throw new Exception('作业不存在');
    }

    if ($action === 'approve') {
        Homework::auditApprove($homeworkId);
        set_flash('success', '审核通过，作业已在画廊公开展示');
    } elseif ($action === 'reject') {
        Homework::auditReject($homeworkId);
        set_flash('success', '已拒绝该作业，图片文件已删除');
    } else {
        throw new Exception('无效的审核操作');
    }
} catch (Exception $e) {
    set_flash('danger', $e->getMessage());
}

redirect(base_url('admin/homeworks.php'));