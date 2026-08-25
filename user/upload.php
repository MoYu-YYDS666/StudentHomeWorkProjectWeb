<?php
/**
 * ============================================================
 * 初高中作业大赏 - 上传作业
 * 文件：user/upload.php
 * 说明：仅已登录且邮箱已验证（status=1）的用户可上传作业图片，
 *       填写本数、页数与描述，服务端校验后保存原图并生成缩略图。
 *       页面顶部展示 config.php 中 UPLOAD_NOTICE 定义的公告框（支持 HTML）。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_login();

// 仅已验证邮箱的用户可上传作业（未验证用户在个人中心重新发送验证邮件）
$current = current_user();
$fullUser = User::findById($current['id']);
if (!$fullUser || (int)$fullUser['status'] !== 1) {
    set_flash('warning', '您的邮箱尚未验证，请先完成邮箱验证后再上传作业');
    redirect(base_url('user/dashboard.php'));
}

$user = current_user();
$errors = [];
$old = ['book_count' => '', 'page_count' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['book_count'] = trim((string)($_POST['book_count'] ?? ''));
    $old['page_count'] = trim((string)($_POST['page_count'] ?? ''));
    $old['description'] = trim((string)($_POST['description'] ?? ''));

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

        // 3. 验证字段
        if (!Validator::integer($old['book_count'], 1, 100000)) {
            throw new Exception('本数必须为正整数（1-100000）');
        }
        if (!Validator::integer($old['page_count'], 1, 100000)) {
            throw new Exception('页数必须为正整数（1-100000）');
        }
        if (!Validator::length($old['description'], 0, 500)) {
            throw new Exception('描述最多 500 字');
        }

        // 4. 图片验证（Homework::create 内部还会再次校验）
        $file = $_FILES['image'] ?? null;
        if (!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('请选择要上传的图片');
        }
        Validator::image($file, MAX_FILE_SIZE);

        // 5. 创建作业
        $data = [
            'book_count'  => (int)$old['book_count'],
            'page_count'  => (int)$old['page_count'],
            'description' => $old['description'],
        ];
        Homework::create($user['id'], $data, $file);

        set_flash('success', '作业上传成功！已提交审核，审核通过后将公开展示');
        redirect(base_url('user/manage.php'));
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require __DIR__ . '/../includes/header.php';
?>

<?php if (defined('UPLOAD_NOTICE') && trim((string)UPLOAD_NOTICE) !== ''): ?>
    <div class="notice-box mb-4">
        <?php echo UPLOAD_NOTICE; /* 公告内容由 config.php 定义，支持 HTML，仅限可信内容 */ ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-4"><i class="bi bi-cloud-arrow-up me-2"></i>上传新作业</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('user/upload.php')) ?>" enctype="multipart/form-data" class="js-geetest-form">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="image">作业图片（仅一张，支持 JPG/PNG/GIF/WEBP，最大 5MB）</label>
                    <input type="file" class="form-control" id="image" name="image"
                           accept="image/jpeg,image/png,image/gif,image/webp" required>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="book_count">本数</label>
                        <input type="number" class="form-control" id="book_count" name="book_count"
                               value="<?= e($old['book_count']) ?>" min="1" max="100000" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="page_count">页数</label>
                        <input type="number" class="form-control" id="page_count" name="page_count"
                               value="<?= e($old['page_count']) ?>" min="1" max="100000" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">描述（选填，最多 500 字）</label>
                    <textarea class="form-control" id="description" name="description" rows="4"
                              maxlength="500"><?= e($old['description']) ?></textarea>
                </div>

                <!-- Geetest4 验证结果隐藏字段 -->
                <input type="hidden" name="geetest_lot_number" id="geetest_lot_number">
                <input type="hidden" name="geetest_captcha_output" id="geetest_captcha_output">
                <input type="hidden" name="geetest_pass_token" id="geetest_pass_token">
                <input type="hidden" name="geetest_gen_time" id="geetest_gen_time">

                <div class="mb-3">
                    <p class="small text-muted mb-2"><i class="bi bi-shield-check me-1"></i>请点击下方按钮完成人机验证</p>
                    <div id="captcha"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100">上 传</button>
            </form>
        </div>
    </div>
</div>

<?php
$geetestPage = new Geetest();
$geetestParams = $geetestPage->getVerifyParams();
?>
<script src="https://static.geetest.com/v4/gt4.js"></script>
<script>
    window.GEETEST_ID = <?= json_encode(GEETEST_ID, JSON_UNESCAPED_UNICODE) ?>;
    window.GEETEST_PARAMS = <?= json_encode($geetestParams, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= e(base_url('assets/js/geetest-init.js')) ?>"></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>