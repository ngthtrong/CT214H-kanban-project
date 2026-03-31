<?php
/**
 * Dashboard / Index Page
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Require authentication
requireLogin();

$user = getCurrentUser();

$pageTitle = 'Dashboard - Kanban Board';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>!</p>
            </div>
            
            <?php displayFlashMessage(); ?>
            
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Dự án của tôi</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center py-4">
                            Chưa có dự án nào.<br>
                            <a href="project-create.php" class="btn btn-primary btn-sm mt-2">Tạo dự án mới</a>
                        </p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Công việc cần làm</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center py-4">
                            Chưa có công việc nào được giao.
                        </p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Hoạt động gần đây</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center py-4">
                            Không có hoạt động gần đây.
                        </p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Thống kê</h2>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number">0</span>
                                <span class="stat-label">Dự án</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">0</span>
                                <span class="stat-label">Công việc</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">0</span>
                                <span class="stat-label">Hoàn thành</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="../js/main.js"></script>
</body>
</html>
