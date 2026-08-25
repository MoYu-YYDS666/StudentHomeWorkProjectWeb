<?php
/**
 * ============================================================
 * 初高中作业大赏 - 公共头部
 * 文件：includes/header.php
 * 说明：输出 HTML 头部、Bootstrap 5 / Bootstrap Icons CDN、
 *       站点样式、响应式导航栏，并在内容区开头输出 Flash 消息。
 * 使用：页面需先 require includes/init.php 再引入本文件。
 * ============================================================
 */
$currentUser = current_user();
$currentFile = basename($_SERVER['PHP_SELF'] ?? '');
$scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>初高学生作业统计Project</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(base_url('index.php')) ?>">
            <i class="bi bi-journal-bookmark-fill me-1"></i>各地初高学生作业统计Project
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= $currentFile === 'index.php' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">
                        <i class="bi bi-images me-1"></i>画廊
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentFile === 'dashboard.php' ? 'active' : '' ?>" href="<?= e(base_url('user/dashboard.php')) ?>">
                            <i class="bi bi-person-circle me-1"></i>个人中心
                        </a>
                    </li>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($scriptName, '/admin/') !== false ? 'active' : '' ?>" href="<?= e(base_url('admin/index.php')) ?>">
                                <i class="bi bi-speedometer2 me-1"></i>管理员后台
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <span class="nav-link text-light-emphasis">
                            <i class="bi bi-person me-1"></i><?= e($currentUser['username']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(base_url('logout.php')) ?>">
                            <i class="bi bi-box-arrow-right me-1"></i>退出
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentFile === 'login.php' ? 'active' : '' ?>" href="<?= e(base_url('login.php')) ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i>登录
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentFile === 'register.php' ? 'active' : '' ?>" href="<?= e(base_url('register.php')) ?>">
                            <i class="bi bi-person-plus me-1"></i>注册
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4 flex-grow-1">
    <?php display_flash(); ?>