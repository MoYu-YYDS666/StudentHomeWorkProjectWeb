<?php
/**
 * ============================================================
 * 初高中作业大赏 - 随机作业 API
 * 文件：api/random.php
 * 说明：公开接口，支持两种模式：
 *   - type=pic 或省略 type：直接输出一张随机公开作业的原图，
 *     浏览器或 <img src="api/random.php"> 即可直接显示图片。
 *   - type=json：返回随机一条公开作业的 JSON 信息。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';

$type = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : 'pic';

// ---------- JSON 模式：返回随机作业信息 ----------
if ($type === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    $homework = Homework::getRandomPublic();

    if (!$homework) {
        echo json_encode(['code' => 1, 'message' => '暂无数据'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'code' => 0,
        'data' => [
            'id'          => (int)$homework['id'],
            'image_url'   => BASE_URL . $homework['image_path'],
            'thumb_url'   => BASE_URL . $homework['thumb_path'],
            'book_count'  => (int)$homework['book_count'],
            'page_count'  => (int)$homework['page_count'],
            'description' => $homework['description'] !== null ? $homework['description'] : '',
            'username'    => $homework['username'],
            'created_at'  => $homework['created_at'],
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 图片模式（默认）：直接输出一张随机公开作业的原图 ----------
$homework = Homework::getRandomPublic();

if (!$homework) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(['code' => 1, 'message' => '暂无数据'], JSON_UNESCAPED_UNICODE);
    exit;
}

// image_path 形如 /uploads/xxx.jpg，转换为服务器绝对路径后输出文件内容
$absPath = str_replace('/uploads/', UPLOAD_PATH, (string)$homework['image_path']);

if (!is_file($absPath)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 2, 'message' => '图片不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

// MIME 类型优先按扩展名映射（上传时已限定 jpg/png/gif/webp），
// 避免依赖 mime_content_type / fileinfo 扩展不可用时返回 application/octet-stream
$ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
$mimeMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
$mime = isset($mimeMap[$ext]) ? $mimeMap[$ext] : '';
if ($mime === '') {
    $detected = @getimagesize($absPath);
    $mime = (is_array($detected) && !empty($detected['mime'])) ? $detected['mime'] : 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($absPath));
header('Cache-Control: public, max-age=3600');
readfile($absPath);
exit;