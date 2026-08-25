<?php
/**
 * ============================================================
 * 初高中作业大赏 - 作业管理
 * 文件：admin/homeworks.php
 * 说明：仅管理员可访问，分页展示全部作业（含隐藏、待审核、未删除），
 *       展示缩略图、描述与审核状态；待审核作业可一键审核通过或
 *       审核拒绝（拒绝直接删除图片）；支持隐藏/显示与软删除操作。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$me = current_user();
$db = Database::getInstance();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

$result = Homework::getAllPaginated($page, $perPage);
$homeworks = $result['data'];
$total = $result['total'];
$totalPages = max(1, (int)ceil($total / $perPage));

// 待审核数量（用于顶部提示）
$pendingCount = (int)$db->fetch('SELECT COUNT(*) AS total FROM homeworks WHERE audit_status = 0 AND is_deleted = 0')['total'];

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-collection me-2"></i>作业管理</h1>
    <div>
        <?php if ($pendingCount > 0): ?>
            <span class="badge text-bg-warning fs-6"><i class="bi bi-hourglass-split me-1"></i>待审核 <?= (int)$pendingCount ?> 份</span>
        <?php else: ?>
            <span class="badge text-bg-success fs-6"><i class="bi bi-check2-all me-1"></i>暂无待审核作业</span>
        <?php endif; ?>
        <span class="text-muted ms-2">共 <?= (int)$total ?> 份作业（未删除）</span>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded admin-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>缩略图</th>
            <th>上传用户</th>
            <th>描述</th>
            <th>本数</th>
            <th>页数</th>
            <th>审核状态</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($homeworks as $homework): ?>
            <?php
            $isPublic = (int)$homework['status'] === 1;
            $auditStatus = (int)$homework['audit_status'];
            $description = ($homework['description'] !== null && $homework['description'] !== '')
                ? (string)$homework['description']
                : '';
            ?>
            <tr>
                <td><?= (int)$homework['id'] ?></td>
                <td>
                    <a href="<?= e(base_url($homework['image_path'])) ?>"
                       class="js-lightbox"
                       data-lightbox-group="admin-gallery"
                       data-caption="<?= e('上传者：' . $homework['username']) ?>">
                        <img src="<?= e(base_url($homework['thumb_path'])) ?>" alt="缩略图" loading="lazy">
                    </a>
                </td>
                <td><?= e($homework['username']) ?></td>
                <td class="hw-desc-cell" title="<?= e($description) ?>">
                    <?= $description !== '' ? e($description) : '<span class="text-muted">（无描述）</span>' ?>
                </td>
                <td><?= (int)$homework['book_count'] ?></td>
                <td><?= (int)$homework['page_count'] ?></td>
                <td>
                    <?php if ($auditStatus === 0): ?>
                        <span class="badge text-bg-warning">待审核</span>
                    <?php elseif ($auditStatus === 1): ?>
                        <span class="badge text-bg-success">已通过</span>
                    <?php else: ?>
                        <span class="badge text-bg-danger">已拒绝</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isPublic): ?>
                        <span class="badge text-bg-info">公开</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">隐藏</span>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap"><?= e(format_datetime($homework['created_at'])) ?></td>
                <td class="table-actions text-nowrap">
                    <?php if ($auditStatus === 0): ?>
                        <form method="post" action="<?= e(base_url('admin/homework_audit.php')) ?>" class="d-inline" data-confirm="确定审核通过这份作业吗？通过后将在画廊公开展示。">
                            <?= csrf_field() ?>
                            <input type="hidden" name="homework_id" value="<?= (int)$homework['id'] ?>">
                            <input type="hidden" name="audit_action" value="approve">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check-lg"></i> 审核通过
                            </button>
                        </form>
                        <form method="post" action="<?= e(base_url('admin/homework_audit.php')) ?>" class="d-inline" data-confirm="确定拒绝这份作业吗？原图与缩略图将被永久删除，无法恢复！">
                            <?= csrf_field() ?>
                            <input type="hidden" name="homework_id" value="<?= (int)$homework['id'] ?>">
                            <input type="hidden" name="audit_action" value="reject">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle"></i> 审核拒绝
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= e(base_url('admin/homework_toggle.php')) ?>" class="d-inline" data-confirm="<?= $isPublic ? '确定要隐藏这份作业吗？' : '确定要公开这份作业吗？' ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="homework_id" value="<?= (int)$homework['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $isPublic ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                <i class="bi <?= $isPublic ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                <?= $isPublic ? '隐藏' : '显示' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= e(base_url('admin/homework_delete.php')) ?>" class="d-inline" data-confirm="确定要删除这份作业吗？">
                        <?= csrf_field() ?>
                        <input type="hidden" name="homework_id" value="<?= (int)$homework['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> 删除
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3" aria-label="分页导航">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">上一页</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">下一页</a></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>