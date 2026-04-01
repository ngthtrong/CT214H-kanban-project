<?php
/**
 * Profile Page
 * Team Kanban - CT214H Final Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';

requireLogin();

$user = getCurrentUser();
$profile = getUserById($user['user_id']);

$errors = [];
$success = '';
$activeTab = $_GET['tab'] ?? 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_profile':
                $result = updateUserProfile($user['user_id'], [
                    'full_name' => $_POST['full_name'] ?? '',
                    'email' => $_POST['email'] ?? ''
                ]);

                if ($result['success']) {
                    $success = 'Cập nhật thông tin thành công!';
                    $profile = getUserById($user['user_id']);
                    updateSessionUser([
                        'full_name' => $profile['full_name'] ?? '',
                        'email' => $profile['email'] ?? ''
                    ]);
                } else {
                    $errors[] = $result['error'];
                }
                $activeTab = 'profile';
                break;

            case 'change_password':
                $result = changeUserPassword(
                    $user['user_id'],
                    $_POST['current_password'] ?? '',
                    $_POST['new_password'] ?? ''
                );

                if ($result['success']) {
                    $success = 'Đổi mật khẩu thành công!';
                } else {
                    $errors[] = $result['error'];
                }
                $activeTab = 'password';
                break;

            case 'upload_avatar':
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $result = uploadUserAvatar($user['user_id'], $_FILES['avatar']);

                    if ($result['success']) {
                        $success = 'Cập nhật avatar thành công!';
                        $profile = getUserById($user['user_id']);
                        updateSessionUser(['avatar' => $profile['avatar'] ?? null]);
                    } else {
                        $errors[] = $result['error'];
                    }
                }
                $activeTab = 'avatar';
                break;

            case 'delete_avatar':
                $currentAvatar = $profile['avatar'] ?? null;
                $result = updateUserAvatar($user['user_id'], null);

                if ($result['success']) {
                    if (!empty($currentAvatar)) {
                        $avatarFile = AVATAR_PATH . $currentAvatar;
                        if (is_file($avatarFile)) {
                            @unlink($avatarFile);
                        }
                    }

                    $success = 'Đã xóa avatar!';
                    $profile = getUserById($user['user_id']);
                    updateSessionUser(['avatar' => null]);
                } else {
                    $errors[] = $result['error'];
                }
                $activeTab = 'avatar';
                break;
        }
    }
}

$pageTitle = 'Hồ sơ cá nhân';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Hồ sơ cá nhân</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="profile-wrapper">
    <div class="profile-sidebar">
        <div class="profile-avatar-section">
            <?php if (!empty($profile['avatar'])): ?>
                <img src="<?php echo asset('uploads/avatars/' . $profile['avatar']); ?>" alt="Avatar" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar profile-avatar-placeholder">
                    <?php echo strtoupper(substr($profile['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($profile['full_name']); ?></h3>
            <p class="text-muted">@<?php echo htmlspecialchars($profile['username']); ?></p>
        </div>

        <nav class="profile-nav">
            <a href="?tab=profile" class="profile-nav-item <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                Thông tin cá nhân
            </a>
            <a href="?tab=password" class="profile-nav-item <?php echo $activeTab === 'password' ? 'active' : ''; ?>">
                Đổi mật khẩu
            </a>
            <a href="?tab=avatar" class="profile-nav-item <?php echo $activeTab === 'avatar' ? 'active' : ''; ?>">
                Avatar
            </a>
        </nav>
    </div>

    <div class="profile-content">
        <?php if ($activeTab === 'profile'): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Thông tin cá nhân</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="profile-form" data-validate="true" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label for="username">Tên đăng nhập</label>
                            <input type="text" id="username"
                                   value="<?php echo htmlspecialchars($profile['username']); ?>"
                                   disabled readonly class="form-control-disabled">
                            <small class="form-hint">Tên đăng nhập không thể thay đổi</small>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Họ và tên <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name"
                                   value="<?php echo htmlspecialchars($profile['full_name']); ?>"
                                   required minlength="2" maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($profile['email']); ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Ngày tham gia</label>
                            <input type="text"
                                   value="<?php echo date('d/m/Y H:i', strtotime($profile['created_at'])); ?>"
                                   disabled readonly class="form-control-disabled">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Lưu thay đổi
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($activeTab === 'password'): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Đổi mật khẩu</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="profile-form" data-validate="true" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Mật khẩu hiện tại <span class="required">*</span></label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password"
                                   required minlength="6">
                            <small class="form-hint">Tối thiểu 6 ký tự</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   required data-match="new_password"
                                   data-error-message="Mật khẩu xác nhận không khớp">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($activeTab === 'avatar'): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Avatar</h2>
                </div>
                <div class="card-body">
                    <div class="avatar-preview">
                        <?php if (!empty($profile['avatar'])): ?>
                            <img src="<?php echo asset('uploads/avatars/' . $profile['avatar']); ?>" alt="Avatar" class="avatar-large">
                        <?php else: ?>
                            <div class="avatar-large avatar-placeholder">
                                <?php echo strtoupper(substr($profile['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="profile-form mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="upload_avatar">

                        <div class="form-group">
                            <label for="avatar">Chọn ảnh mới</label>
                            <input type="file" id="avatar" name="avatar"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="form-hint">JPEG, PNG, GIF, WEBP. Tối đa 5MB.</small>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                Upload Avatar
                            </button>
                            <?php if (!empty($profile['avatar'])): ?>
                                <button type="submit" name="action" value="delete_avatar"
                                        class="btn btn-danger"
                                        onclick="return confirm('Bạn có chắc muốn xóa avatar?')">
                                    Xóa Avatar
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
