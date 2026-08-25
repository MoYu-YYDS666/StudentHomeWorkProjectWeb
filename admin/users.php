<?php
/**
 * ============================================================
 * 初高中作业大赏 - 用户管理
 * 文件：admin/users.php
 * 说明：仅管理员可访问，分页展示全部用户（含最近登录 IP），
 *       支持禁用/启用与删除（禁止操作自己的账号）。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$me = current_user();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

$result = User::listAllUsers($page, $perPage);
$users = $result['data'];
$total = $result['total'];
$totalPages = max(1, (int)ceil($total / $perPage));

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>用户管理</h1>
    <span class="text-muted">共 <?= (int)$total ?> 位用户</span>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded admin-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>用户名</th>
            <th>邮箱</th>
            <th>角色</th>
            <th>状态</th>
            <th>注册时间</th>
            <th>最近登录 IP</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <?php
            $isMe = (int)$user['id'] === $me['id'];
            $status = (int)$user['status'];
            ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td>
                    <?= e($user['username']) ?>
                    <?php if ($isMe): ?><span class="badge text-bg-info">我</span><?php endif; ?>
                </td>
                <td><?= e($user['email']) ?></td>
                <td>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge text-bg-danger">管理员</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">用户</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status === 0): ?>
                        <span class="badge text-bg-warning">未验证</span>
                    <?php elseif ($status === 1): ?>
                        <span class="badge text-bg-success">正常</span>
                    <?php else: ?>
                        <span class="badge text-bg-danger">禁用</span>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap"><?= e(format_datetime($user['created_at'])) ?></td>
                <td class="text-nowrap">
                    <?php if (!empty($user['last_login_ip'])): ?>
                        <span class="js-ip-loc" data-ip="<?= e($user['last_login_ip']) ?>"><?= e($user['last_login_ip']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">从未登录</span>
                    <?php endif; ?>
                </td>
                <td class="table-actions text-nowrap">
                    <?php if ($isMe): ?>
                        <span class="text-muted small">不可操作自己</span>
                    <?php else: ?>
                        <form method="post" action="<?= e(base_url('admin/user_toggle.php')) ?>" class="d-inline" data-confirm="<?= $status === 1 ? '确定要禁用该用户吗？' : '确定要启用该用户吗？' ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $status === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                <i class="bi <?= $status === 1 ? 'bi-person-slash' : 'bi-person-check' ?>"></i>
                                <?= $status === 1 ? '禁用' : '启用' ?>
                            </button>
                        </form>
                        <form method="post" action="<?= e(base_url('admin/user_delete.php')) ?>" class="d-inline" data-confirm="确定要删除该用户吗？其全部作业将一并删除，此操作不可恢复！">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> 删除
                            </button>
                        </form>
                    <?php endif; ?>
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