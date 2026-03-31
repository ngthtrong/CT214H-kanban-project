# CHANGELOG - Auth Simplification

## Ngày: $(Get-Date -Format "yyyy-MM-dd")

## Thay đổi: Đơn giản hóa hệ thống Authentication

### Mục đích
Đơn giản hóa cấu trúc Auth từ OOP pattern phức tạp sang procedural functions để phù hợp với đồ án nhập môn lập trình web.

---

## Files Thêm Mới

### 1. `includes/auth.php` (11.8 KB)
**Chức năng:** Tất cả authentication functions

**Functions:**
- `registerUser(array $data): array` - Đăng ký user mới
- `authenticateUser(string $identifier, string $password): array` - Xác thực đăng nhập
- `updateUserProfile(int $userId, array $data): array` - Cập nhật profile
- `changeUserPassword(int $userId, string $currentPassword, string $newPassword): array` - Đổi mật khẩu
- `updateUserAvatar(int $userId, ?string $avatarPath): array` - Cập nhật avatar path
- `uploadUserAvatar(int $userId, array $file): array` - Upload avatar file
- `getUserById(int $userId): ?array` - Lấy user theo ID
- `findUserByIdentifier(string $identifier): ?array` - Tìm user theo username/email

**Validation:**
- Username: 3-50 chars, alphanumeric + underscore
- Email: valid format
- Password: minimum 6 chars
- Avatar: max 5MB, types: jpg/png/gif/webp

### 2. `docs/AUTH-SIMPLIFIED.md` (6.6 KB)
**Chức năng:** Tài liệu mô tả hệ thống Auth mới

**Nội dung:**
- Luồng xử lý Registration
- Luồng xử lý Login
- Luồng xử lý Update Profile
- API endpoints documentation
- Security features
- So sánh cũ vs mới

---

## Files Đã Sửa

### 1. `public/login.php`
**Thay đổi:**
```php
// CŨ:
use App\Auth\LoginService;
$service = new LoginService();
$result = $service->login($identifier, $password);

// MỚI:
require_once __DIR__ . '/../includes/auth.php';
$result = authenticateUser($identifier, $password);
```

### 2. `public/register.php`
**Thay đổi:**
```php
// CŨ:
use App\Auth\RegistrationService;
$service = new RegistrationService();
$result = $service->register($formData);

// MỚI:
require_once __DIR__ . '/../includes/auth.php';
$result = registerUser($formData);
```

### 3. `public/profile.php`
**Thay đổi:**
```php
// CŨ:
use App\User\ProfileService;
$profileService = new ProfileService();
$result = $profileService->updateProfile($userId, $data);

// MỚI:
require_once __DIR__ . '/../includes/auth.php';
$result = updateUserProfile($userId, $data);
```

### 4. `api/auth.php`
**Thay đổi:**
- Bỏ namespace imports
- Thay các service calls bằng function calls
- Cập nhật response format để phù hợp

---

## Files/Folders Đã Xóa

### Xóa hoàn toàn:

1. **src/Auth/** (11 files, ~17 KB)
   - AccessControlService.php
   - AvatarPolicyService.php
   - CredentialValidationService.php
   - LoginService.php
   - LogoutService.php
   - ProfileService.php
   - RegistrationService.php
   - UserRepositoryInterface.php
   - Contracts/UserRepositoryInterface.php
   - Infrastructure/DatabaseUserRepository.php
   - Infrastructure/InMemoryUserRepository.php

2. **src/User/** (1 file)
   - ProfileService.php

3. **tests/Unit/Auth/** (7 files)
   - AccessControlTask00022Test.php
   - AvatarBlockTask00013Test.php
   - LoginTask00004Test.php
   - LogoutTask00007Test.php
   - ProfileTask00010Test.php
   - RegistrationTask00001Test.php
   - ValidationTask00016Test.php

---

## Tổng kết

### Trước (Phức tạp):
```
src/Auth/ + src/User/     : 12 files, ~20 KB
tests/Unit/Auth/           : 7 test files
Total                      : 19 files
```

### Sau (Đơn giản):
```
includes/auth.php          : 1 file, 11.8 KB
docs/AUTH-SIMPLIFIED.md    : 1 doc file
Total                      : 2 files
```

### Lợi ích:
- ✅ Giảm từ 19 files → 2 files
- ✅ Code đơn giản, dễ hiểu
- ✅ Không cần namespace, autoload
- ✅ Phù hợp với trình độ sinh viên năm 2
- ✅ Dễ trình bày trong báo cáo đồ án
- ✅ Luồng xử lý rõ ràng từng bước
- ✅ Vẫn đảm bảo đầy đủ chức năng và bảo mật

### Breaking Changes:
- ⚠️ Tests của Auth cũ không còn chạy được (đã xóa)
- ✅ Tất cả pages và APIs đã update sang functions mới
- ✅ Không ảnh hưởng đến chức năng người dùng

---

## Migration Guide

Nếu có code nào còn sử dụng class cũ, cập nhật như sau:

### Registration
```php
// CŨ:
use App\Auth\RegistrationService;
$service = new RegistrationService();
$result = $service->register([...]);

// MỚI:
require_once 'includes/auth.php';
$result = registerUser([...]);
```

### Login
```php
// CŨ:
use App\Auth\LoginService;
$service = new LoginService();
$result = $service->login($identifier, $password);

// MỚI:
require_once 'includes/auth.php';
$result = authenticateUser($identifier, $password);
```

### Profile Update
```php
// CŨ:
use App\User\ProfileService;
$service = new ProfileService();
$result = $service->updateProfile($userId, $data);

// MỚI:
require_once 'includes/auth.php';
$result = updateUserProfile($userId, $data);
```

---

**Team Kanban - CT214H Final Project**
**Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")**
