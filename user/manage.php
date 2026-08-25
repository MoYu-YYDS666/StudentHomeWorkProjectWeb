<?php
/**
 * ============================================================
 * 初高中作业大赏 - 我的作业管理
 * 文件：user/manage.php
 * 说明：分页展示当前用户的作业（含隐藏、未删除），展示审核状态，
 *       提供编辑、删除、隐藏/显示操作入口；本周已编辑过的作业禁用编辑。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

$result = Homework::getByUserPaginated($user['id'], $page, $perPage);
$homeworks = $result['data'];
$total = $result['total'];
$totalPages = max(1, (int)ceil($total / $perPage));

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-collection me-2"></i>我的作业</h1>
    <a href="<?= e(base_url('user/upload.php')) ?>" class="btn btn-primary">
        <i class="bi bi-cloud-arrow-up me-1"></i>上传新作业
    </a>
</div>

<?php if (empty($homeworks)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox display-1 text-muted"></i>
        <p class="mt-3 text-muted fs-5">还没有上传过作业</p>
        <a href="<?= e(base_url('user/upload.php')) ?>" class="btn btn-primary">立即上传</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white shadow-sm rounded">
            <thead class="table-dark">
            <tr>
                <th>缩略图</th>
                <th>本数</th>
                <th>页数</th>
                <th>描述</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($homeworks as $homework): ?>
                <?php
                $editable = Homework::isEditAllowed((int)$homework['id'], $user['id']);
                $isPublic = (int)$homework['status'] === 1;
                $auditStatus = (int)$homework['audit_status'];
                ?>
                <tr>
                    <td>
                        <img src="<?= e(base_url($homework['thumb_path'])) ?>" alt="缩略图" loading="lazy">
                    </td>
                    <td><?= (int)$homework['book_count'] ?></td>
                    <td><?= (int)$homework['page_count'] ?></td>
                    <td class="text-truncate" style="max-width:180px;" title="<?= e($homework['description'] ?? '') ?>">
                        <?= e($homework['description'] !== null && $homework['description'] !== '' ? $homework['description'] : '（无描述）') ?>
                    </td>
                    <td>
                        <?php if ($auditStatus === 0): ?>
                            <span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i>审核中</span>
                        <?php elseif ($auditStatus === 1): ?>
                            <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>已通过</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>已拒绝</span>
                        <?php endif; ?>
                        <?php if ($isPublic): ?>
                            <span class="badge text-bg-info">公开</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">隐藏</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap"><?= e(format_datetime($homework['created_at'])) ?></td>
                    <td class="table-actions text-nowrap">
                        <?php if ($editable): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('user/edit.php?id=' . (int)$homework['id'])) ?>">
                                <i class="bi bi-pencil-square"></i> 编辑
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="本周已编辑过，每周仅限编辑一次">
                                <i class="bi bi-pencil-square"></i> 本周已编辑
                            </button>
                        <?php endif; ?>

                        <form method="post" action="<?= e(base_url('user/hide.php')) ?>" class="d-inline" data-confirm="<?= $isPublic ? '确定要隐藏这份作业吗？' : '确定要公开这份作业吗？' ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$homework['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $isPublic ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                <i class="bi <?= $isPublic ? 'bi-eye-slash' : 'bi-eye' ?>"></i> <?= $isPublic ? '隐藏' : '显示' ?>
                            </button>
                        </form>

                        <form method="post" action="<?= e(base_url('user/delete.php')) ?>" class="d-inline" data-confirm="确定要删除这份作业吗？删除后画廊中将不再显示。">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$homework['id'] ?>">
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
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>