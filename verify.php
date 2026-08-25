<?php
/**
 * ============================================================
 * 初高中作业大赏 - 邮箱验证
 * 文件：verify.php
 * 说明：接收 GET 参数 token 和 email，调用 User::verifyEmail
 *       完成邮箱验证，验证失败提示重新发送入口。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

$token = trim((string)($_GET['token'] ?? ''));
$email = trim((string)($_GET['email'] ?? ''));

$success = false;
$errorMessage = '';

try {
    User::verifyEmail($token, $email);
    $success = true;
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 text-center">
            <?php if ($success): ?>
                <i class="bi bi-check-circle-fill text-success display-3"></i>
                <h1 class="h4 mt-3">验证成功，现在可以登录</h1>
                <p class="text-muted">您的邮箱已通过验证，账号已激活。</p>
                <a href="<?= e(base_url('login.php')) ?>" class="btn btn-primary mt-2">前往登录</a>
            <?php else: ?>
                <i class="bi bi-x-circle-fill text-danger display-3"></i>
                <h1 class="h4 mt-3">验证失败</h1>
                <p class="text-muted"><?= e($errorMessage ?: '链接无效或已过期') ?></p>
                <p class="small text-muted">验证链接 24 小时内有效，过期后请登录个人中心重新发送验证邮件。</p>
                <a href="<?= e(base_url('login.php')) ?>" class="btn btn-outline-primary mt-2">返回登录</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>