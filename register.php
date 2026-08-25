<?php
/**
 * ============================================================
 * 初高中作业大赏 - 用户注册
 * 文件：register.php
 * 说明：注册表单包含 Geetest4 滑动验证；注册成功后生成邮箱验证
 *       Token 并发送验证邮件，提示用户查收邮件完成验证。
 *       邮件发送失败时仍可登录，在个人中心重新发送验证邮件。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

// 已登录用户直接跳转到个人中心
if (is_logged_in()) {
    redirect(base_url('user/dashboard.php'));
}

$errors = [];
$old = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim((string)($_POST['username'] ?? ''));
    $old['email'] = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    try {
        // 1. 验证 CSRF
        verify_csrf();

        // 2. Geetest4 服务端二次校验
        $geetest = new Geetest();
        if (!$geetest->verify(
            (string)($_POST['geetest_lot_number'] ?? ''),
            (string)($_POST['geetest_captcha_output'] ?? ''),
            (string)($_POST['geetest_pass_token'] ?? ''),
            (string)($_POST['geetest_gen_time'] ?? '')
        )) {
            throw new Exception('滑动验证失败，请重试');
        }

        // 3. 表单字段验证
        if (!Validator::username($old['username'])) {
            throw new Exception('用户名长度为 3-20 位，只能包含字母、数字和下划线');
        }
        if (!Validator::email($old['email'])) {
            throw new Exception('邮箱格式不正确');
        }
        if (!Validator::password($password)) {
            throw new Exception('密码长度至少 6 位');
        }
        if ($password !== $passwordConfirm) {
            throw new Exception('两次输入的密码不一致');
        }

        // 4. 注册用户
        $userId = User::register($old['username'], $old['email'], $password);

        // 5. 生成验证 Token 并发送邮件
        $token = User::createEmailToken($userId);
        try {
            Mailer::sendVerificationEmail($old['email'], $token);
            set_flash('success', '注册成功，验证邮件已发送，请前往邮箱完成验证');
        } catch (Exception $mailError) {
            // 注册成功但邮件发送失败：用户仍可登录，稍后在个人中心重新发送
            set_flash('warning', '注册成功，但验证邮件发送失败，登录后可在个人中心重新发送；若持续失败请联系管理员');
        }

        redirect(base_url('login.php'));
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Geetest4 前端初始化参数
$geetest = new Geetest();
$geetestParams = $geetest->getVerifyParams();

require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-4"><i class="bi bi-person-plus me-2"></i>注册</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('register.php')) ?>" class="js-geetest-form">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="username">用户名</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= e($old['username']) ?>" placeholder="3-20 位，字母、数字或下划线"
                           minlength="3" maxlength="20" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">邮箱</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($old['email']) ?>" placeholder="用于接收验证邮件" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">密码</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="至少 6 位" minlength="6" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirm">确认密码</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                           placeholder="请再次输入密码" minlength="6" required>
                </div>

                <!-- Geetest4 验证结果隐藏字段 -->
                <input type="hidden" name="geetest_lot_number" id="geetest_lot_number">
                <input type="hidden" name="geetest_captcha_output" id="geetest_captcha_output">
                <input type="hidden" name="geetest_pass_token" id="geetest_pass_token">
                <input type="hidden" name="geetest_gen_time" id="geetest_gen_time">
                <p class="small text-muted mb-2"><i class="bi bi-shield-check me-1"></i>请点击下方按钮完成人机验证</p>
                <div id="captcha" class="mb-3"></div>

                <button type="submit" class="btn btn-primary w-100">注 册</button>
            </form>

            <p class="text-center text-muted mt-3 mb-0">
                已有账号？<a href="<?= e(base_url('login.php')) ?>">直接登录</a>
                <span class="mx-1">|</span>
                <a href="<?= e(base_url('forgot_password.php')) ?>" class="text-decoration-none">忘记密码？</a>
            </p>
        </div>
    </div>
</div>

<script src="https://static.geetest.com/v4/gt4.js"></script>
<script>
    window.GEETEST_ID = <?= json_encode(GEETEST_ID, JSON_UNESCAPED_UNICODE) ?>;
    window.GEETEST_PARAMS = <?= json_encode($geetestParams, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= e(base_url('assets/js/geetest-init.js')) ?>"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>