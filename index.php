<?php
/**
 * ============================================================
 * 初高中作业大赏 - 画廊首页
 * 文件：index.php
 * 说明：公开访问，展示公开作业卡片网格（每页 12 张），
 *       顶部展示全站统计横栏（总本数 / 总页数），
 *       卡片显示缩略图、上传者、描述与本数/页数；
 *       点击缩略图通过 Fancybox 弹出大图并展示作业信息。
 * 布局：Bootstrap 网格平铺，等高卡片，双端自适应
 *       （电脑 4 列 / 平板 3 列 / 手机 2 列）。
 * ============================================================
 */
require_once __DIR__ . '/includes/init.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

$result = Homework::getPublicPaginated($page, $perPage);
$homeworks = $result['data'];
$total = $result['total'];
$totalPages = max(1, (int)ceil($total / $perPage));

// 全站统计（仅统计审核通过、公开且未删除的作业）
$stats = Homework::getStats();
$totalBooks = (int)$stats['total_books'];
$totalPagesCount = (int)$stats['total_pages'];

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-images me-2"></i>作业画廊</h1>
    <span class="text-muted">共 <?= (int)$total ?> 份作业</span>
</div>

<div class="stats-bar">
    <div class="stats-item">
        <i class="bi bi-book stats-icon"></i>
        <span class="stats-label">已统计总本数：</span>
        <span class="stats-number"><?= (int)$totalBooks ?></span>
        <span class="stats-unit">本</span>
    </div>
    <div class="stats-divider" aria-hidden="true"></div>
    <div class="stats-item">
        <i class="bi bi-file-earmark-text stats-icon"></i>
        <span class="stats-label">已统计总页数：</span>
        <span class="stats-number"><?= (int)$totalPagesCount ?></span>
        <span class="stats-unit">页</span>
    </div>
</div>

<?php if (empty($homeworks)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox display-1 text-muted"></i>
        <p class="mt-3 text-muted fs-5">暂无作业，快来上传吧</p>
        <?php if (is_logged_in()): ?>
            <a href="<?= e(base_url('user/upload.php')) ?>" class="btn btn-primary">上传作业</a>
        <?php else: ?>
            <a href="<?= e(base_url('register.php')) ?>" class="btn btn-primary">注册后上传</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3 g-md-4">
        <?php foreach ($homeworks as $homework): ?>
            <?php
            $description = ($homework['description'] !== null && $homework['description'] !== '')
                ? (string)$homework['description']
                : '';
            $caption = '<div class="hw-caption">'
                . '<div class="hw-caption-desc">' . e($description !== '' ? $description : '（暂无描述）') . '</div>'
                . '<div class="hw-caption-meta">上传者：' . e($homework['username'])
                . ' ｜ 本数：' . (int)$homework['book_count']
                . ' ｜ 页数：' . (int)$homework['page_count']
                . ' ｜ 上传时间：' . e(format_datetime($homework['created_at'])) . '</div></div>';
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card gallery-card h-100">
                    <a href="<?= e(base_url($homework['image_path'])) ?>" data-fancybox="gallery" data-caption="<?= e($caption) ?>">
                        <img src="<?= e(base_url($homework['thumb_path'])) ?>"
                             alt="作业图片（<?= e($homework['username']) ?>）"
                             class="gallery-thumb" loading="lazy">
                    </a>
                    <div class="card-body d-flex flex-column">
                        <p class="card-title mb-2"><i class="bi bi-person-circle me-1"></i><?= e($homework['username']) ?></p>
                        <?php if ($description !== ''): ?>
                            <p class="gallery-desc" title="<?= e($description) ?>"><?= e($description) ?></p>
                        <?php endif; ?>
                        <div class="mt-auto">
                            <span class="badge text-bg-primary"><i class="bi bi-book me-1"></i><?= (int)$homework['book_count'] ?> 本</span>
                            <span class="badge text-bg-secondary"><i class="bi bi-file-earmark-text me-1"></i><?= (int)$homework['page_count'] ?> 页</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="分页导航">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> 上一页</a>
                    </li>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">下一页 <i class="bi bi-chevron-right"></i></a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>