<?php
/**
 * ============================================================
 * 初高中作业大赏 - 重新发送验证邮件
 * 文件：user/resend_verify.php
 * 说明：仅登录用户可访问；默认使用当前登录用户邮箱，
 *       也可通过 GET 参数 email 指定（仅限本人邮箱）。
 *       发送失败时明确提示用户联系管理员。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user();
$full = User::findById($user['id']);

$email = trim((string)($_GET['email'] ?? ''));
if ($email === '') {
    $email = $full['email'] ?? '';
}
// 仅允许操作自己的邮箱
if ($email !== ($full['email'] ?? '')) {
    set_flash('danger', '只能为当前登录账号重新发送验证邮件');
    redirect(base_url('user/dashboard.php'));
}

$message = '';
$messageType = 'success';

try {
    $token = User::resendVerification($email);
    try {
        Mailer::sendVerificationEmail($email, $token);
        $message = '验证邮件已重新发送，请查收';
    } catch (Exception $mailError) {
        // 邮件发送失败（网络或 SMTP 问题），明确提示用户
        error_log('[Mailer] 验证邮件发送失败: ' . $mailError->getMessage());
        $message = '验证邮件发送失败，请稍后重试；若持续失败请联系管理员';
        $messageType = 'danger';
    }
} catch (Exception $e) {
    // 业务层异常（账号已激活/已禁用等）保留原提示
    $message = $e->getMessage();
    $messageType = 'danger';
}

require __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 text-center">
            <i class="bi <?= $messageType === 'success' ? 'bi-envelope-check text-success' : 'bi-exclamation-circle text-danger' ?> display-3"></i>
            <h1 class="h4 mt-3"><?= $messageType === 'success' ? '邮件已发送' : '发送失败' ?></h1>
            <p class="text-muted"><?= e($message) ?></p>
            <p class="small text-muted">收件邮箱：<?= e($email) ?>（验证链接 24 小时内有效）</p>
            <a href="<?= e(base_url('user/dashboard.php')) ?>" class="btn btn-primary mt-2">返回个人中心</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>