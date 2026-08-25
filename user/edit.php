<?php
/**
 * ============================================================
 * 初高中作业大赏 - 编辑作业
 * 文件：user/edit.php
 * 说明：仅本人可编辑，每周限编辑一次；可选重新上传图片。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$homework = Homework::getById($id);
if (!$homework || (int)$homework['is_deleted'] === 1) {
    set_flash('danger', '作业不存在');
    redirect(base_url('user/manage.php'));
}
if ((int)$homework['user_id'] !== $user['id']) {
    set_flash('danger', '无权操作该作业');
    redirect(base_url('user/manage.php'));
}

// 本周已编辑过则拒绝编辑
if (!Homework::isEditAllowed($id, $user['id'])) {
    set_flash('warning', '本周已编辑过，每周仅限编辑一次');
    redirect(base_url('user/manage.php'));
}

$errors = [];
$old = [
    'book_count'  => (string)$homework['book_count'],
    'page_count'  => (string)$homework['page_count'],
    'description' => (string)($homework['description'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['book_count'] = trim((string)($_POST['book_count'] ?? ''));
    $old['page_count'] = trim((string)($_POST['page_count'] ?? ''));
    $old['description'] = trim((string)($_POST['description'] ?? ''));

    try {
        verify_csrf();

        if (!Validator::integer($old['book_count'], 1, 100000)) {
            throw new Exception('本数必须为正整数（1-100000）');
        }
        if (!Validator::integer($old['page_count'], 1, 100000)) {
            throw new Exception('页数必须为正整数（1-100000）');
        }
        if (!Validator::length($old['description'], 0, 500)) {
            throw new Exception('描述最多 500 字');
        }

        $file = $_FILES['image'] ?? null;
        // 可选上传：未选择新图片时传 null 保留原图
        if (is_array($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            Validator::image($file, MAX_FILE_SIZE);
        } else {
            $file = null;
        }

        $data = [
            'book_count'  => (int)$old['book_count'],
            'page_count'  => (int)$old['page_count'],
            'description' => $old['description'],
        ];
        Homework::update($id, $user['id'], $data, $file);

        set_flash('success', '作业编辑成功，修改后将重新进入审核');
        redirect(base_url('user/manage.php'));
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-4"><i class="bi bi-pencil-square me-2"></i>编辑作业</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mb-3">
                <img src="<?= e(base_url($homework['thumb_path'])) ?>" alt="当前图片" class="rounded shadow-sm" style="max-height:160px;">
                <p class="small text-muted mt-2 mb-0">当前图片（如不重新上传则保留原图）</p>
            </div>

            <form method="post" action="<?= e(base_url('user/edit.php?id=' . $id)) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="image">重新上传图片（选填，JPG/PNG/GIF/WEBP，最大 5MB）</label>
                    <input type="file" class="form-control" id="image" name="image"
                           accept="image/jpeg,image/png,image/gif,image/webp">
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

                <button type="submit" class="btn btn-primary w-100">保存修改</button>
                <a href="<?= e(base_url('user/manage.php')) ?>" class="btn btn-outline-secondary w-100 mt-2">返回管理页</a>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>