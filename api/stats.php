<?php
/**
 * ============================================================
 * 初高中作业大赏 - 统计 API
 * 文件：api/stats.php
 * 说明：公开接口，返回公开作业的总本数与总页数。
 * ============================================================
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$stats = Homework::getStats();

echo json_encode([
    'code' => 0,
    'data' => [
        'total_books' => (int)$stats['total_books'],
        'total_pages' => (int)$stats['total_pages'],
    ],
], JSON_UNESCAPED_UNICODE);