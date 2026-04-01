<?php
/**
 * Registration Page
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = false;
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        // Sanitize and validate input
        $formData = [
            'username' => sanitize($_POST['username'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];
        
        // Confirm password check
        if ($formData['password'] !== $formData['password_confirm']) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        } else {
            // Register user
            $result = registerUser($formData);
            
            if ($result['success']) {
                $success = true;
                flash('Đăng ký thành công! Bạn có thể đăng nhập ngay.', 'success');
                redirect('login.php');
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

$pageTitle = 'Đăng ký - Kanban Board';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?= assetVersioned('css/style.css') ?>">
    <link rel="stylesheet" href="<?= assetVersioned('css/responsive.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Kanban Board</h1>
                <p>Tạo tài khoản mới</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" data-validate="true" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($formData['username']); ?>"
                        placeholder="Nhập tên đăng nhập"
                        required
                        minlength="3"
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]+"
                        data-error-message="Tên đăng nhập chỉ chứa chữ cái, số và dấu gạch dưới"
                    >
                    <small class="form-hint">3-50 ký tự, chỉ chứa chữ cái, số và dấu gạch dưới</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($formData['email']); ?>"
                        placeholder="example@email.com"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="full_name">Họ và tên <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                        placeholder="Nguyễn Văn A"
                        required
                        minlength="2"
                        maxlength="100"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu <span class="required">*</span></label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Nhập mật khẩu"
                        required
                        minlength="6"
                    >
                    <small class="form-hint">Tối thiểu 6 ký tự</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Xác nhận mật khẩu <span class="required">*</span></label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        placeholder="Nhập lại mật khẩu"
                        required
                        data-match="password"
                        data-error-message="Mật khẩu xác nhận không khớp"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Đăng ký
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?= assetVersioned('js/main.js') ?>"></script>
</body>
</html>
