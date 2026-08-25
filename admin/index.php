<?php
/**
 * ============================================================
 * 初高中作业大赏 - 管理员后台首页
 * 文件：admin/index.php
 * 说明：仅管理员可访问，展示全站统计信息、待审核作业提醒与管理入口。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$db = Database::getInstance();

$totalUsers = (int)$db->fetch('SELECT COUNT(*) AS total FROM users')['total'];
$totalHomeworks = (int)$db->fetch('SELECT COUNT(*) AS total FROM homeworks WHERE is_deleted = 0')['total'];
$pendingCount = (int)$db->fetch('SELECT COUNT(*) AS total FROM homeworks WHERE audit_status = 0 AND is_deleted = 0')['total'];
$stats = $db->fetch(
    'SELECT COALESCE(SUM(book_count), 0) AS total_books, COALESCE(SUM(page_count), 0) AS total_pages
     FROM homeworks WHERE is_deleted = 0'
);

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-speedometer2 me-2"></i>管理员后台</h1>
    <div>
        <a href="<?= e(base_url('admin/users.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-people me-1"></i>用户管理
        </a>
        <a href="<?= e(base_url('admin/homeworks.php')) ?>" class="btn btn-outline-primary">
            <i class="bi bi-collection me-1"></i>作业管理
            <?php if ($pendingCount > 0): ?>
                <span class="badge text-bg-warning ms-1">待审核 <?= (int)$pendingCount ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<?php if ($pendingCount > 0): ?>
    <div class="alert alert-warning d-flex align-items-center">
        <i class="bi bi-hourglass-split me-2 fs-5"></i>
        <span>当前有 <strong><?= (int)$pendingCount ?></strong> 份作业等待审核，<a href="<?= e(base_url('admin/homeworks.php')) ?>">前往作业管理</a> 处理。</span>
    </div>
<?php endif; ?>

<div class="row g-3 g-md-4">
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 text-center p-4">
            <i class="bi bi-people display-4 text-primary"></i>
            <h2 class="h1 mt-2"><?= (int)$totalUsers ?></h2>
            <p class="text-muted mb-0">总用户数</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 text-center p-4">
            <i class="bi bi-collection display-4 text-success"></i>
            <h2 class="h1 mt-2"><?= (int)$totalHomeworks ?></h2>
            <p class="text-muted mb-0">总作业数（未删除）</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 text-center p-4">
            <i class="bi bi-book display-4 text-warning"></i>
            <h2 class="h1 mt-2"><?= (int)$stats['total_books'] ?></h2>
            <p class="text-muted mb-0">总本数（未删除）</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 text-center p-4">
            <i class="bi bi-file-earmark-text display-4 text-info"></i>
            <h2 class="h1 mt-2"><?= (int)$stats['total_pages'] ?></h2>
            <p class="text-muted mb-0">总页数（未删除）</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>