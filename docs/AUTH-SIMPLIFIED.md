# Authentication System - Simplified Version

## Tổng quan

Hệ thống authentication đã được **đơn giản hóa hoàn toàn** từ OOP pattern phức tạp sang **procedural functions** để phù hợp với môn Nhập môn Lập trình Web.

### Lý do đơn giản hóa:
- ✅ Dễ hiểu và trình bày trong báo cáo
- ✅ Luồng xử lý rõ ràng, từng bước
- ✅ Không cần namespace, autoload, dependency injection
- ✅ Phù hợp với trình độ sinh viên năm 2

---

## Cấu trúc mới (Simplified)

```
includes/
└── auth.php            # Tất cả functions authentication

api/
└── auth.php            # API endpoints (sử dụng functions từ includes/auth.php)

public/
├── login.php           # Trang đăng nhập
├── register.php        # Trang đăng ký
└── profile.php         # Trang quản lý profile
```

---

## Functions trong `includes/auth.php`

### 1. Registration
```php
registerUser(array $data): array
```
- Input: `username`, `email`, `password`, `full_name`
- Validate: required fields, username format, email format, password length
- Check: duplicate username/email
- Output: `['success' => true/false, 'user_id' => int, 'error' => string]`

### 2. Authentication
```php
authenticateUser(string $identifier, string $password): array
```
- Input: username/email, password
- Find user by identifier
- Verify password with `password_verify()`
- Output: `['success' => true/false, 'user' => array, 'error' => string]`

### 3. Profile Management
```php
updateUserProfile(int $userId, array $data): array
changeUserPassword(int $userId, string $currentPassword, string $newPassword): array
updateUserAvatar(int $userId, ?string $avatarPath): array
uploadUserAvatar(int $userId, array $file): array
```

### 4. User Retrieval
```php
getUserById(int $userId): ?array
findUserByIdentifier(string $identifier): ?array
```

---

## Luồng xử lý Registration

```
1. User submit form tại register.php
   ├── CSRF token verification
   ├── Password confirmation check
   └── Call: registerUser($formData)

2. registerUser() function
   ├── Validate input (required, format)
   ├── Check duplicate username/email
   ├── Hash password với password_hash()
   ├── Insert vào database với dbInsert()
   └── Return result

3. register.php xử lý kết quả
   ├── Success: flash message + redirect to login.php
   └── Error: hiển thị lỗi
```

---

## Luồng xử lý Login

```
1. User submit form tại login.php
   ├── CSRF token verification
   └── Call: authenticateUser($identifier, $password)

2. authenticateUser() function
   ├── Find user by username OR email
   ├── Verify password với password_verify()
   └── Return user data (without password)

3. login.php xử lý kết quả
   ├── Success: 
   │   ├── Call loginUser() từ session.php
   │   └── Redirect to dashboard
   └── Error: hiển thị lỗi
```

---

## Luồng xử lý Update Profile

```
1. User submit form tại profile.php
   ├── CSRF token verification
   ├── Check action (update_profile/change_password/upload_avatar)
   └── Call tương ứng:
       ├── updateUserProfile()
       ├── changeUserPassword()
       └── uploadUserAvatar()

2. Functions xử lý
   ├── Validate input
   ├── Check duplicate (for email/username)
   ├── Update database
   └── Return result

3. profile.php cập nhật session
   └── $_SESSION['user'] = getUserById($userId)
```

---

## API Endpoints (api/auth.php)

### POST /api/auth.php?action=register
```json
Request:
{
  "username": "user123",
  "email": "user@example.com",
  "full_name": "Nguyen Van A",
  "password": "123456"
}

Response (success):
{
  "success": true,
  "data": {"user_id": 1},
  "message": "Đăng ký thành công"
}
```

### POST /api/auth.php?action=login
```json
Request:
{
  "identifier": "user123",
  "password": "123456",
  "remember": false
}

Response (success):
{
  "success": true,
  "data": {
    "user_id": 1,
    "username": "user123",
    "email": "user@example.com",
    "full_name": "Nguyen Van A",
    "avatar": null
  },
  "message": "Đăng nhập thành công"
}
```

### POST /api/auth.php?action=logout
```json
Response:
{
  "success": true,
  "message": "Đăng xuất thành công"
}
```

### GET /api/auth.php?action=me
Trả về thông tin user hiện tại (requires authentication)

### PUT /api/auth.php?action=update-profile
Update full_name, email

### POST /api/auth.php?action=change-password
Change password với current_password verification

---

## Security Features

### 1. Password Hashing
```php
// Register
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Login
if (password_verify($password, $user['password'])) { ... }
```

### 2. Input Validation
- Required fields check
- Format validation (email, username pattern)
- Length validation
- Duplicate check

### 3. CSRF Protection
- Token generation: `generateCSRFToken()`
- Token verification: `verifyCSRFToken()`

### 4. SQL Injection Prevention
- Prepared statements qua `dbQuery()`, `dbInsert()`, `dbUpdate()`

### 5. File Upload Security
- MIME type validation
- File size limit (5MB)
- Allowed types: jpg, png, gif, webp
- Unique filename generation

---

## So sánh với cấu trúc cũ

### Cũ (Phức tạp - OOP):
```
src/Auth/
├── RegistrationService.php        (7KB, 200+ lines)
├── LoginService.php                (4KB, 150+ lines)
├── LogoutService.php
├── ProfileService.php
├── AccessControlService.php
├── AvatarPolicyService.php
├── CredentialValidationService.php
├── Contracts/
│   └── UserRepositoryInterface.php
└── Infrastructure/
    ├── DatabaseUserRepository.php
    └── InMemoryUserRepository.php
```
- **11 files**, phức tạp với Repository pattern, Dependency Injection
- Khó giải thích trong báo cáo

### Mới (Đơn giản - Procedural):
```
includes/
└── auth.php                        (10KB, tất cả functions)
```
- **1 file**, tất cả logic ở một chỗ
- Dễ đọc, dễ hiểu, dễ trình bày

---

## Testing

Để test các functions:

```php
// Test registration
$result = registerUser([
    'username' => 'test123',
    'email' => 'test@example.com',
    'password' => '123456',
    'full_name' => 'Test User'
]);

// Test login
$result = authenticateUser('test123', '123456');

// Test profile update
$result = updateUserProfile(1, [
    'full_name' => 'New Name',
    'email' => 'new@example.com'
]);
```

---

## Kết luận

Cấu trúc mới:
- ✅ Đơn giản, dễ hiểu
- ✅ Phù hợp với đồ án nhập môn
- ✅ Đầy đủ chức năng
- ✅ Bảo mật cơ bản đảm bảo
- ✅ Dễ trình bày và giải thích luồng xử lý

**Team Kanban - CT214H Final Project**
