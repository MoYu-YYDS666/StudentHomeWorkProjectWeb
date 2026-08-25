<?php
/**
 * ============================================================
 * 初高中作业大赏 - 作业业务逻辑类
 * 文件：classes/Homework.php
 * 说明：作业创建、查询、编辑、软删除、隐藏/显示、审核、统计、
 *       随机获取、图片校验与缩略图生成等业务。所有 SQL 使用 PDO 预处理。
 * 审核：上传与编辑后的作业需管理员审核（audit_status：
 *       0待审核 / 1审核通过 / 2已拒绝），通过后才会在画廊公开展示。
 * ============================================================
 */

class Homework
{
    /**
     * 获取数据库单例
     */
    private static function db()
    {
        return Database::getInstance();
    }

    /**
     * 创建作业（验证字段 -> 保存图片并生成缩略图 -> 插入数据库）
     * 新作业默认 audit_status=0（待审核），审核通过前不会公开展示。
     * @param int $userId 当前用户 ID
     * @param array $data 包含 book_count、page_count、description
     * @param array $file $_FILES 中的图片文件数组
     * @return int 新作业 ID
     * @throws Exception
     */
    public static function create($userId, $data, $file)
    {
        $userId = (int)$userId;
        $bookCount = isset($data['book_count']) ? (int)$data['book_count'] : 0;
        $pageCount = isset($data['page_count']) ? (int)$data['page_count'] : 0;
        $description = isset($data['description']) ? trim((string)$data['description']) : '';

        if (!Validator::integer($bookCount, 1, 100000)) {
            throw new Exception('本数必须为 1-100000 的整数');
        }
        if (!Validator::integer($pageCount, 1, 100000)) {
            throw new Exception('页数必须为 1-100000 的整数');
        }
        if (!Validator::length($description, 0, 500)) {
            throw new Exception('描述最多 500 字');
        }

        $paths = self::saveImage($file);

        try {
            self::db()->execute(
                'INSERT INTO homeworks (user_id, image_path, thumb_path, book_count, page_count, description, audit_status)
                 VALUES (?, ?, ?, ?, ?, ?, 0)',
                [$userId, $paths['image_path'], $paths['thumb_path'], $bookCount, $pageCount, $description === '' ? null : $description]
            );
        } catch (Exception $e) {
            // 入库失败时清理已保存的图片文件
            self::deleteImageFiles($paths['image_path'], $paths['thumb_path']);
            throw new Exception('保存作业失败，请稍后重试');
        }

        return (int)self::db()->lastInsertId();
    }

    /**
     * 画廊分页查询（仅审核通过、公开且未删除，按创建时间倒序）
     * @return array ['data' => 作业列表, 'total' => 总数]
     */
    public static function getPublicPaginated($page, $perPage)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $db = self::db();

        $total = (int)$db->fetch('SELECT COUNT(*) AS total FROM homeworks WHERE status = 1 AND is_deleted = 0 AND audit_status = 1')['total'];
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            'SELECT h.*, u.username
             FROM homeworks h
             INNER JOIN users u ON u.id = h.user_id
             WHERE h.status = 1 AND h.is_deleted = 0 AND h.audit_status = 1
             ORDER BY h.created_at DESC
             LIMIT :offset, :limit'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * 根据 ID 查询作业（附带上传者用户名）
     */
    public static function getById($id)
    {
        return self::db()->fetch(
            'SELECT h.*, u.username
             FROM homeworks h
             INNER JOIN users u ON u.id = h.user_id
             WHERE h.id = ? LIMIT 1',
            [(int)$id]
        );
    }

    /**
     * 当前用户作业分页（包含隐藏、未删除）
     * @return array ['data' => 作业列表, 'total' => 总数]
     */
    public static function getByUserPaginated($userId, $page, $perPage)
    {
        $userId = (int)$userId;
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $db = self::db();

        $total = (int)$db->fetch('SELECT COUNT(*) AS total FROM homeworks WHERE user_id = ? AND is_deleted = 0', [$userId])['total'];
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            'SELECT * FROM homeworks
             WHERE user_id = :user_id AND is_deleted = 0
             ORDER BY created_at DESC
             LIMIT :offset, :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * 管理员查询全部作业（包含隐藏、待审核、未删除），
     * 待审核的作业排在前面便于优先处理
     * @return array ['data' => 作业列表, 'total' => 总数]
     */
    public static function getAllPaginated($page, $perPage)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $db = self::db();

        $total = (int)$db->fetch('SELECT COUNT(*) AS total FROM homeworks WHERE is_deleted = 0')['total'];
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            'SELECT h.*, u.username
             FROM homeworks h
             INNER JOIN users u ON u.id = h.user_id
             WHERE h.is_deleted = 0
             ORDER BY h.audit_status ASC, h.created_at DESC
             LIMIT :offset, :limit'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * 编辑作业：校验归属与本周编辑限制，可选重新上传图片。
     * 编辑成功后重置为待审核（audit_status=0），需管理员重新审核。
     * @param int $id 作业 ID
     * @param int $userId 当前用户 ID
     * @param array $data 包含 book_count、page_count、description
     * @param array|null $file 可选的新图片文件数组
     * @return bool
     * @throws Exception
     */
    public static function update($id, $userId, $data, $file = null)
    {
        $id = (int)$id;
        $userId = (int)$userId;

        $homework = self::getById($id);
        if (!$homework || (int)$homework['is_deleted'] === 1) {
            throw new Exception('作业不存在');
        }
        if ((int)$homework['user_id'] !== $userId) {
            throw new Exception('无权操作该作业');
        }
        if (!empty($homework['last_edited_at']) && self::inCurrentWeek($homework['last_edited_at'])) {
            throw new Exception('本周已编辑过，每周仅限编辑一次');
        }

        $bookCount = isset($data['book_count']) ? (int)$data['book_count'] : 0;
        $pageCount = isset($data['page_count']) ? (int)$data['page_count'] : 0;
        $description = isset($data['description']) ? trim((string)$data['description']) : '';

        if (!Validator::integer($bookCount, 1, 100000)) {
            throw new Exception('本数必须为 1-100000 的整数');
        }
        if (!Validator::integer($pageCount, 1, 100000)) {
            throw new Exception('页数必须为 1-100000 的整数');
        }
        if (!Validator::length($description, 0, 500)) {
            throw new Exception('描述最多 500 字');
        }

        $imagePath = $homework['image_path'];
        $thumbPath = $homework['thumb_path'];

        // 可选重新上传图片：保存新图并删除旧图
        if ($file !== null && isset($file['name']) && $file['name'] !== '') {
            $paths = self::saveImage($file);
            self::deleteImageFiles($imagePath, $thumbPath);
            $imagePath = $paths['image_path'];
            $thumbPath = $paths['thumb_path'];
        }

        self::db()->execute(
            'UPDATE homeworks
             SET image_path = ?, thumb_path = ?, book_count = ?, page_count = ?,
                 description = ?, last_edited_at = NOW(), audit_status = 0
             WHERE id = ?',
            [$imagePath, $thumbPath, $bookCount, $pageCount, $description === '' ? null : $description, $id]
        );

        return true;
    }

    /**
     * 软删除作业（仅本人或管理员可操作，不删除图片文件）
     * @param int $id 作业 ID
     * @param int $userId 当前用户 ID
     * @throws Exception 作业不存在或无权操作
     */
    public static function softDelete($id, $userId)
    {
        $homework = self::getById($id);
        if (!$homework || (int)$homework['is_deleted'] === 1) {
            throw new Exception('作业不存在');
        }
        if (!User::isAdmin((int)$userId) && (int)$homework['user_id'] !== (int)$userId) {
            throw new Exception('无权操作该作业');
        }

        self::db()->execute('UPDATE homeworks SET is_deleted = 1 WHERE id = ?', [(int)$id]);
        return true;
    }

    /**
     * 切换作业公开状态（0 隐藏 / 1 公开），仅本人或管理员可操作
     * @return int 新状态
     * @throws Exception 作业不存在或无权操作
     */
    public static function toggleStatus($id, $userId)
    {
        $homework = self::getById($id);
        if (!$homework || (int)$homework['is_deleted'] === 1) {
            throw new Exception('作业不存在');
        }
        if (!User::isAdmin((int)$userId) && (int)$homework['user_id'] !== (int)$userId) {
            throw new Exception('无权操作该作业');
        }

        $newStatus = ((int)$homework['status'] === 1) ? 0 : 1;
        self::db()->execute('UPDATE homeworks SET status = ? WHERE id = ?', [$newStatus, (int)$id]);
        return $newStatus;
    }

    /**
     * 审核通过作业（仅管理员可操作）
     * 通过后自动设为公开（status=1），作业将在画廊公开展示
     * @param int $id 作业 ID
     * @return bool
     * @throws Exception 作业不存在或已删除
     */
    public static function auditApprove($id)
    {
        $homework = self::getById($id);
        if (!$homework) {
            throw new Exception('作业不存在');
        }
        if ((int)$homework['is_deleted'] === 1) {
            throw new Exception('作业已删除，无法审核');
        }

        self::db()->execute(
            'UPDATE homeworks SET audit_status = 1, status = 1 WHERE id = ?',
            [(int)$id]
        );
        return true;
    }

    /**
     * 审核拒绝作业（仅管理员可操作）
     * 拒绝后直接删除原图与缩略图文件，并物理删除数据库记录
     * @param int $id 作业 ID
     * @return bool
     * @throws Exception 作业不存在
     */
    public static function auditReject($id)
    {
        $homework = self::getById($id);
        if (!$homework || (int)$homework['is_deleted'] === 1) {
            throw new Exception('作业不存在');
        }

        self::deleteImageFiles($homework['image_path'], $homework['thumb_path']);
        self::db()->execute('DELETE FROM homeworks WHERE id = ?', [(int)$id]);
        return true;
    }

    /**
     * 判断作业当前是否可编辑（存在、属于该用户、本周未编辑过）
     * @param int $id 作业 ID
     * @param int $userId 当前用户 ID
     * @return bool
     */
    public static function isEditAllowed($id, $userId)
    {
        $homework = self::getById($id);
        if (!$homework || (int)$homework['user_id'] !== (int)$userId) {
            return false;
        }
        if (!empty($homework['last_edited_at']) && self::inCurrentWeek($homework['last_edited_at'])) {
            return false;
        }
        return true;
    }

    /**
     * 判断时间是否处于当前自然周内（周一 00:00:00 至周日 23:59:59）
     * @param string $datetime 日期时间
     * @return bool
     */
    public static function inCurrentWeek($datetime)
    {
        if (empty($datetime)) {
            return false;
        }
        $timestamp = strtotime($datetime);
        $weekStart = strtotime('monday this week 00:00:00');
        $weekEnd = strtotime('sunday this week 23:59:59');
        return $timestamp >= $weekStart && $timestamp <= $weekEnd;
    }

    /**
     * 统计总本数与总页数（仅统计审核通过、公开且未删除的作业）
     * @return array ['total_books' => 总本数, 'total_pages' => 总页数]
     */
    public static function getStats()
    {
        $row = self::db()->fetch(
            'SELECT COALESCE(SUM(book_count), 0) AS total_books, COALESCE(SUM(page_count), 0) AS total_pages
             FROM homeworks WHERE status = 1 AND is_deleted = 0 AND audit_status = 1'
        );

        return [
            'total_books' => (int)$row['total_books'],
            'total_pages' => (int)$row['total_pages'],
        ];
    }

    /**
     * 随机返回一条公开作业（仅审核通过，含上传者用户名）
     */
    public static function getRandomPublic()
    {
        return self::db()->fetch(
            'SELECT h.*, u.username
             FROM homeworks h
             INNER JOIN users u ON u.id = h.user_id
             WHERE h.status = 1 AND h.is_deleted = 0 AND h.audit_status = 1
             ORDER BY RAND() LIMIT 1'
        );
    }

    /**
     * 校验上传图片：类型、大小、真实性（getimagesize + finfo）
     * @param array $file $_FILES 中的图片文件数组
     * @return string 图片扩展名（jpg / png / gif / webp）
     * @throws Exception
     */
    public static function validateImage($file)
    {
        if (!is_array($file)) {
            throw new Exception('请选择要上传的图片');
        }
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('请选择要上传的图片');
        }
        if (is_array($file['error'])) {
            throw new Exception('文件上传参数错误');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new Exception('文件大小超过限制（最大 5MB）');
            }
            throw new Exception('文件上传失败，请重试');
        }
        if ((int)$file['size'] > (int)MAX_FILE_SIZE) {
            throw new Exception('文件大小超过限制（最大 5MB）');
        }
        if ((int)$file['size'] <= 0) {
            throw new Exception('文件内容为空');
        }

        // getimagesize 确认是真实图片
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new Exception('文件不是有效的图片');
        }

        // finfo 校验真实 MIME 类型，扩展名白名单
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMap = [
                'image/jpeg'  => 'jpg',
                'image/pjpeg' => 'jpg',
                'image/png'   => 'png',
                'image/gif'   => 'gif',
                'image/webp'  => 'webp',
            ];
            if (!isset($allowedMap[$mime])) {
                throw new Exception('仅支持 JPG、PNG、GIF、WEBP 格式图片');
            }
            return $allowedMap[$mime];
        }

        // 无 finfo 扩展时按 getimagesize 的 MIME 判断
        $allowedMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($allowedMap[$info['mime']])) {
            throw new Exception('仅支持 JPG、PNG、GIF、WEBP 格式图片');
        }
        return $allowedMap[$info['mime']];
    }

    /**
     * 保存原图并生成宽度 400px 的缩略图
     * @param array $file $_FILES 中的图片文件数组
     * @return array ['image_path' => 原图 URL 路径, 'thumb_path' => 缩略图 URL 路径]
     * @throws Exception
     */
    public static function saveImage($file)
    {
        $ext = self::validateImage($file);

        if (!is_dir(UPLOAD_PATH) || !is_writable(UPLOAD_PATH)) {
            throw new Exception('上传目录不可写，请联系管理员');
        }
        if (!is_dir(THUMB_PATH) || !is_writable(THUMB_PATH)) {
            throw new Exception('缩略图目录不可写，请联系管理员');
        }

        // 随机文件名，防止路径穿越与重名覆盖
        $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $imageAbs = UPLOAD_PATH . $name;
        $thumbAbs = THUMB_PATH . $name;

        if (!move_uploaded_file($file['tmp_name'], $imageAbs)) {
            throw new Exception('图片保存失败，请重试');
        }

        try {
            self::createThumbnail($imageAbs, $thumbAbs, 400);
        } catch (Exception $e) {
            @unlink($imageAbs);
            throw $e;
        }

        return [
            'image_path' => '/uploads/' . $name,
            'thumb_path' => '/thumbnails/' . $name,
        ];
    }

    /**
     * 使用 GD 生成缩略图（宽度固定，高度按原图比例缩放）
     * @param string $src 原图绝对路径
     * @param string $dst 缩略图绝对路径
     * @param int $width 缩略图宽度
     * @throws Exception
     */
    private static function createThumbnail($src, $dst, $width = 400)
    {
        $info = @getimagesize($src);
        if ($info === false) {
            throw new Exception('图片处理失败');
        }

        switch ($info['mime']) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($src);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($src);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($src);
                break;
            default:
                throw new Exception('不支持的图片格式');
        }

        if (!$image) {
            throw new Exception('图片处理失败');
        }

        $srcW = imagesx($image);
        $srcH = imagesy($image);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($image);
            throw new Exception('图片尺寸异常');
        }

        $newH = (int)round($srcH * ($width / $srcW));
        $thumb = imagecreatetruecolor($width, $newH);

        // 保留透明背景（PNG / GIF / WEBP）
        $isTransparent = in_array($info['mime'], ['image/png', 'image/gif', 'image/webp'], true);
        if ($isTransparent) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $newH, $srcW, $srcH);

        switch ($info['mime']) {
            case 'image/jpeg':
                imagejpeg($thumb, $dst, 85);
                break;
            case 'image/png':
                imagepng($thumb, $dst);
                break;
            case 'image/gif':
                imagegif($thumb, $dst);
                break;
            case 'image/webp':
                imagewebp($thumb, $dst, 85);
                break;
        }

        imagedestroy($image);
        imagedestroy($thumb);
    }

    /**
     * 删除原图与缩略图文件（文件不存在时静默跳过）
     * @param string $imagePath 原图 URL 路径
     * @param string $thumbPath 缩略图 URL 路径
     */
    public static function deleteImageFiles($imagePath, $thumbPath)
    {
        $map = ['/uploads/' => UPLOAD_PATH, '/thumbnails/' => THUMB_PATH];
        foreach ([$imagePath, $thumbPath] as $path) {
            if (empty($path)) {
                continue;
            }
            $abs = str_replace(array_keys($map), array_values($map), (string)$path);
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }
}