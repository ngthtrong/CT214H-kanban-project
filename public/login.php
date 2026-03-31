<?php
/**
 * Login Page
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$identifier = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Attempt login
        $result = authenticateUser($identifier, $password);
        
        if ($result['success']) {
            // Login successful - create session
            loginUser($result['user'], $remember);
            
            // Redirect to intended page or dashboard
            $redirectTo = $_GET['redirect'] ?? 'index.php';
            redirect($redirectTo);
        } else {
            $errors[] = $result['error'];
        }
    }
}

$pageTitle = 'Đăng nhập - Kanban Board';
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
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Kanban Board</h1>
                <p>Đăng nhập vào tài khoản</p>
            </div>
            
            <?php displayFlashMessage(); ?>
            
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
                    <label for="identifier">Tên đăng nhập hoặc Email</label>
                    <input 
                        type="text" 
                        id="identifier" 
                        name="identifier" 
                        value="<?php echo htmlspecialchars($identifier); ?>"
                        placeholder="Nhập tên đăng nhập hoặc email"
                        required
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Nhập mật khẩu"
                        required
                    >
                </div>
                
                <div class="form-group form-group-checkbox">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        <span>Ghi nhớ đăng nhập</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Đăng nhập
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
</body>
</html>
