<?php
/**
 * Header Template
 * Team Kanban - CT214H Final Project
 */

// Load config and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/session.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$currentUser = $isLoggedIn ? getCurrentUser() : null;

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Team Kanban - Hệ thống quản lý dự án nhóm">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= assetVersioned('css/style.css') ?>">
    
    <?php if (isset($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
        <link rel="stylesheet" href="<?= assetVersioned($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <link rel="stylesheet" href="<?= assetVersioned('css/responsive.css') ?>">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <!-- Logo -->
                <a href="<?= APP_URL ?>" class="navbar-brand">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                    <span>Team Kanban</span>
                </a>
                
                <!-- Mobile Toggle -->
                <button class="navbar-toggle" aria-label="Toggle navigation" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Navigation Links -->
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                    <li>
                        <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/search.php" class="nav-link <?= $currentPage === 'search' ? 'active' : '' ?>">
                            Tìm kiếm
                        </a>
                    </li>
                    <?php else: ?>
                    <li>
                        <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                            Trang chủ
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- User Menu -->
                <div class="navbar-user">
                    <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <button class="btn btn-secondary" data-dropdown-toggle aria-haspopup="true">
                            <span class="avatar avatar-sm">
                                <?php if ($currentUser['avatar']): ?>
                                <img src="<?= avatarUrl($currentUser['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </span>
                            <span class="hide-mobile"><?= sanitize($currentUser['full_name']) ?></span>
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?= APP_URL ?>/profile.php" class="dropdown-item">
                                Hồ sơ cá nhân
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?= APP_URL ?>/logout.php" class="dropdown-item text-danger">
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="<?= APP_URL ?>/login.php" class="btn btn-outline <?= $currentPage === 'login' ? 'active' : '' ?>">
                        Đăng nhập
                    </a>
                    <a href="<?= APP_URL ?>/register.php" class="btn btn-primary <?= $currentPage === 'register' ? 'active' : '' ?>">
                        Đăng ký
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?= displayFlash() ?>
