<?php
/**
 * ============================================================
 * 初高中作业大赏 - 用户登录
 * 文件：login.php
 * 说明：登录表单包含 Geetest4 滑动验证，服务端二次校验；
 *       成功后按角色跳转，支持同站 redirect 参数。
 *       未验证邮箱的用户允许登录，可在个人中心重新发送验证邮件。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

// 已登录用户直接跳转
if (is_logged_in()) {
    $loggedUser = current_user();
    redirect($loggedUser['role'] === 'admin' ? base_url('admin/index.php') : base_url('user/dashboard.php'));
}

$errors = [];
$identifier = '';
$redirectUrl = trim((string)($_GET['redirect'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));

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

        // 3. 验证账号密码
        $user = User::login($identifier, (string)($_POST['password'] ?? ''));

        // 4. 写入会话（防止会话固定）
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        User::updateLastLogin((int)$user['id'], get_client_ip());

        set_flash('success', '登录成功，欢迎回来！');

        // 5. 同站 redirect 校验后跳转
        if ($redirectUrl !== '') {
            $targetHost = parse_url($redirectUrl, PHP_URL_HOST);
            $baseHost = parse_url(BASE_URL, PHP_URL_HOST);
            if (($targetHost !== null && $targetHost === $baseHost) || ($targetHost === null && strpos($redirectUrl, '/') === 0)) {
                redirect($redirectUrl);
            }
        }

        redirect($user['role'] === 'admin' ? base_url('admin/index.php') : base_url('user/dashboard.php'));
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
            <h1 class="h4 text-center mb-4"><i class="bi bi-box-arrow-in-right me-2"></i>登录</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post"
                  action="<?= e(base_url('login.php' . ($redirectUrl !== '' ? '?redirect=' . urlencode($redirectUrl) : ''))) ?>"
                  class="js-geetest-form">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="identifier">用户名或邮箱</label>
                    <input type="text" class="form-control" id="identifier" name="identifier"
                           value="<?= e($identifier) ?>" placeholder="请输入用户名或邮箱" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">密码</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="请输入密码" required>
                </div>

                <div class="mb-3 text-end">
                    <a href="<?= e(base_url('forgot_password.php')) ?>" class="small text-decoration-none">
                        <i class="bi bi-key me-1"></i>忘记密码？
                    </a>
                </div>

                <!-- Geetest4 验证结果隐藏字段 -->
                <input type="hidden" name="geetest_lot_number" id="geetest_lot_number">
                <input type="hidden" name="geetest_captcha_output" id="geetest_captcha_output">
                <input type="hidden" name="geetest_pass_token" id="geetest_pass_token">
                <input type="hidden" name="geetest_gen_time" id="geetest_gen_time">
                <p class="small text-muted mb-2"><i class="bi bi-shield-check me-1"></i>请点击下方按钮完成人机验证</p>
                <div id="captcha" class="mb-3"></div>

                <button type="submit" class="btn btn-primary w-100">登 录</button>
            </form>

            <p class="text-center text-muted mt-3 mb-0">
                还没有账号？<a href="<?= e(base_url('register.php')) ?>">立即注册</a>
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