<?php
/**
 * ============================================================
 * 初高中作业大赏 - 忘记密码（发送重置邮件）
 * 文件：forgot_password.php
 * 说明：输入注册邮箱，向该邮箱发送密码重置链接（24 小时有效）。
 *       无论邮箱是否存在均提示同一句话，避免泄露账号注册状态。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

// 已登录用户无需找回密码
if (is_logged_in()) {
    redirect(base_url('user/dashboard.php'));
}

$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    try {
        verify_csrf();

        if (!Validator::email($email)) {
            throw new Exception('邮箱格式不正确');
        }

        // 生成重置 Token（账号不存在或被禁用时返回 null，不发送邮件）
        $token = User::createResetToken($email);
        if ($token !== null) {
            Mailer::sendPasswordResetEmail($email, $token);
        }

        // 统一提示，不暴露账号是否存在
        $success = true;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-2"><i class="bi bi-key me-2"></i>忘记密码</h1>
            <p class="text-center text-muted small mb-4">
                请输入注册邮箱，我们将发送密码重置链接（24 小时内有效）
            </p>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-envelope-check me-2"></i>
                    如果该邮箱已注册，重置密码邮件已发送，请前往邮箱查收。
                </div>
                <p class="text-center mb-0">
                    <a href="<?= e(base_url('login.php')) ?>" class="btn btn-outline-primary btn-sm">返回登录</a>
                </p>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('forgot_password.php')) ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="email">注册邮箱</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($email) ?>" placeholder="请输入注册时使用的邮箱" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">发送重置邮件</button>
                </form>

                <p class="text-center text-muted mt-3 mb-0">
                    <a href="<?= e(base_url('login.php')) ?>">返回登录</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>