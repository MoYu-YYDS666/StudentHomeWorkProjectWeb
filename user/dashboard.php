<?php
/**
 * ============================================================
 * 初高中作业大赏 - 个人中心首页
 * 文件：user/dashboard.php
 * 说明：仅登录用户可访问，展示账号信息（含最近登录 IP）与
 *       功能入口；未验证邮箱时展示“发送验证邮件”卡片。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user();
$full = User::findById($user['id']);
if (!$full) {
    set_flash('danger', '用户不存在，请重新登录');
    redirect(base_url('logout.php'));
}

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center p-4">
                <i class="bi bi-person-circle display-1 text-primary"></i>
                <h1 class="h4 mt-2"><?= e($full['username']) ?></h1>
                <p class="text-muted mb-2">
                    <?= $full['role'] === 'admin' ? '<span class="badge text-bg-danger">管理员</span>' : '<span class="badge text-bg-secondary">普通用户</span>' ?>
                </p>
                <hr>
                <ul class="list-unstyled text-start small">
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i><?= e($full['email']) ?></li>
                    <li class="mb-2"><i class="bi bi-calendar3 me-2"></i>注册时间：<?= e(format_datetime($full['created_at'])) ?></li>
                    <li class="mb-2"><i class="bi bi-clock-history me-2"></i>最后登录：<?= e(format_datetime($full['last_login_at']) ?: '从未登录') ?></li>
                    <li class="mb-2"><i class="bi bi-globe2 me-2"></i>最近登录 IP：
                        <?php if (!empty($full['last_login_ip'])): ?>
                            <span class="js-ip-loc" data-ip="<?= e($full['last_login_ip']) ?>"><?= e($full['last_login_ip']) ?></span>
                        <?php else: ?>
                            从未登录
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ((int)$full['status'] === 0): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="h6 mb-1"><i class="bi bi-envelope-exclamation me-2 text-warning"></i>发送验证邮件</h2>
                        <p class="small text-muted mb-0">您的邮箱尚未验证，完成验证后才能上传作业。请点击右侧按钮重新发送验证邮件。</p>
                    </div>
                    <a href="<?= e(base_url('user/resend_verify.php')) ?>" class="btn btn-warning">发送验证邮件</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-sm-6">
                <a href="<?= e(base_url('user/upload.php')) ?>" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 text-center p-4">
                        <i class="bi bi-cloud-arrow-up display-4 text-primary"></i>
                        <h2 class="h6 mt-3">上传新作业</h2>
                        <p class="small text-muted mb-0">分享你的初高中作业</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-6">
                <a href="<?= e(base_url('user/manage.php')) ?>" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 text-center p-4">
                        <i class="bi bi-collection display-4 text-success"></i>
                        <h2 class="h6 mt-3">管理我的作业</h2>
                        <p class="small text-muted mb-0">编辑、隐藏或删除作业</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-6">
                <a href="<?= e(base_url('index.php')) ?>" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 text-center p-4">
                        <i class="bi bi-images display-4 text-info"></i>
                        <h2 class="h6 mt-3">返回画廊</h2>
                        <p class="small text-muted mb-0">浏览大家的作业</p>
                    </div>
                </a>
            </div>
            <?php if ($full['role'] === 'admin'): ?>
                <div class="col-sm-6">
                    <a href="<?= e(base_url('admin/index.php')) ?>" class="text-decoration-none">
                        <div class="card shadow-sm border-0 h-100 text-center p-4">
                            <i class="bi bi-speedometer2 display-4 text-danger"></i>
                            <h2 class="h6 mt-3">管理员后台</h2>
                            <p class="small text-muted mb-0">用户与作业管理</p>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>