<?php
/**
 * ============================================================
 * 初高中作业大赏 - 重置密码
 * 文件：reset_password.php
 * 说明：通过邮件中的重置链接（token + email）进入本页，
 *       校验通过后设置新密码，成功后跳转登录页。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

$token = trim((string)($_GET['token'] ?? ''));
$email = trim((string)($_GET['email'] ?? ''));
$errors = [];
$validToken = false;

if ($token !== '' && $email !== '') {
    $validToken = User::validateResetToken($token, $email);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string)($_POST['token'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    try {
        verify_csrf();

        if ($password !== $passwordConfirm) {
            throw new Exception('两次输入的密码不一致');
        }
        if (!Validator::password($password)) {
            throw new Exception('新密码长度至少 6 位');
        }

        User::resetPassword($token, $email, $password);

        set_flash('success', '密码重置成功，请使用新密码登录');
        redirect(base_url('login.php'));
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    $validToken = User::validateResetToken($token, $email);
}

require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-4"><i class="bi bi-shield-lock me-2"></i>重置密码</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="post" action="<?= e(base_url('reset_password.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="email" value="<?= e($email) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="password">新密码</label>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="至少 6 位" minlength="6" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password_confirm">确认新密码</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                               placeholder="请再次输入新密码" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">确认重置</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    重置链接无效或已过期，请重新申请。
                </div>
                <p class="text-center mb-0">
                    <a href="<?= e(base_url('forgot_password.php')) ?>" class="btn btn-primary btn-sm">重新申请</a>
                    <a href="<?= e(base_url('login.php')) ?>" class="btn btn-outline-secondary btn-sm">返回登录</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>