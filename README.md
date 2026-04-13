# Team Kanban - CT214H Final Project

Team Kanban là ứng dụng quản lý công việc nhóm theo mô hình Kanban, phát triển cho đồ án môn Lập Trình Web (CT214H).

Ứng dụng cho phép:
- Đăng ký, đăng nhập, quản lý hồ sơ cá nhân
- Tạo project, mời thành viên, tham gia bằng mã project
- Quản lý task (CRUD), gán việc, claim việc, kéo thả trạng thái
- Tìm kiếm và lọc task theo nhiều tiêu chí
- Upload tài liệu đính kèm cho task
- Lưu trữ (archive) project và task

## 1. Công nghệ sử dụng

- Backend: PHP 8.x (procedural)
- Database: MySQL / MariaDB
- Frontend: HTML5, CSS3, JavaScript (vanilla)
- Môi trường local đề xuất: XAMPP trên Windows

## 2. Các tính năng chính

### Authentication
- Đăng ký tài khoản
- Đăng nhập bằng username hoặc email
- Đăng xuất
- Cập nhật thông tin cá nhân và avatar

### Project và Member
- Tạo project mới
- Phát sinh mã project để mời/tham gia
- Xem danh sách project đang tham gia
- Thêm/xóa thành viên
- Gửi và duyệt yêu cầu tham gia

### Task Management
- Tạo, xem, sửa, xóa task
- Gán task cho member, hoặc để unassigned
- Member có thể claim task chưa được gán
- Drag and drop task giữa 3 cột: todo, in_progress, done
- Upload, download, xóa file đính kèm

### Search / Filter / Pagination
- Tìm theo từ khóa (task_title, description)
- Lọc theo status, priority, assigned_to
- Hỗ trợ lọc theo due date, overdue, due_this_week qua API
- Phân trang kết quả ở backend (mặc định 10 item/trang)

### Archive (Soft Delete)
- Archive/unarchive project
- Archive/unarchive task
- Tách dữ liệu đang hoạt động và dữ liệu lưu trữ

## 3. Cấu trúc thư mục

```
CT214H-kanban-project/
|- api/                  # REST-like endpoints
|- css/                  # Giao diện
|- database/             # schema.sql, sample-data.sql, migrations/
|- docs/                 # Tài liệu đặc tả, brief, changelog
|- includes/             # config, db, session, business functions
|- js/                   # Logic frontend
|- report/               # Báo cáo LaTeX
|- uploads/              # attachments, avatars
|- index.php             # Dashboard
|- project.php           # Kanban board
|- search.php            # Trang tìm kiếm task
```

## 4. Cài đặt nhanh trên XAMPP (Windows)

### Bước 1: Đặt source code
Đặt source vào:

`C:\xampp\htdocs\CT214H-kanban-project`

### Bước 2: Mở XAMPP Control Panel
- Start Apache
- Start MySQL

### Bước 3: Tạo database và import dữ liệu
Có 2 cách:

#### Cách A - phpMyAdmin (đề nghị)
1. Mở `http://localhost/phpmyadmin`
2. Tạo database: `kanban_db` (utf8mb4)
3. Import file:
   - `database/schema.sql`
   - `database/sample-data.sql`

#### Cách B - command line
Trong terminal tại root project:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS kanban_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& "C:\xampp\mysql\bin\mysql.exe" -u root kanban_db < database\schema.sql
& "C:\xampp\mysql\bin\mysql.exe" -u root kanban_db < database\sample-data.sql
```

### Bước 4: Chạy migration (nếu DB cũ)
Nếu bạn đã tạo DB từ trước và gặp lỗi thiếu cột `is_archived`, chạy thêm:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root kanban_db < database\migrations\2026-04-02-add-archive-columns.sql
```

### Bước 5: Kiểm tra quyền ghi thư mục upload
Đảm bảo các thư mục tồn tại và có quyền ghi:
- `uploads/avatars/`
- `uploads/attachments/`

### Bước 6: Truy cập ứng dụng
Mở:

`http://localhost/CT214H-kanban-project`

## 5. Tài khoản mẫu (sample-data.sql)

Tất cả tài khoản mẫu dùng chung mật khẩu:

`ngthtrong`

Tài khoản:
- username: `ngthtrong1` - email: `ngthtrong1@example.com`
- username: `ngthtrong2` - email: `ngthtrong2@example.com`
- username: `ngthtrong3` - email: `ngthtrong3@example.com`
- username: `ngthtrong4` - email: `ngthtrong4@example.com`
- username: `ngthtrong5` - email: `ngthtrong5@example.com`
- username: `ngthtrong6` - email: `ngthtrong6@example.com`
- username: `ngthtrong7` - email: `ngthtrong7@example.com`
- username: `ngthtrong8` - email: `ngthtrong8@example.com`
- username: `ngthtrong9` - email: `ngthtrong9@example.com`
- username: `ngthtrong10` - email: `ngthtrong10@example.com`

## 6. Cấu hình quan trọng

File cấu hình: `includes/config.php`

Giá trị mặc định:
- DB_HOST = localhost
- DB_PORT = 3306
- DB_DATABASE = kanban_db
- DB_USERNAME = root
- DB_PASSWORD = (rỗng)
- APP_URL = http://localhost/CT214H-kanban-project
- ITEMS_PER_PAGE = 10

Bạn có thể override qua environment variables (getenv).

## 7. API overview

### Auth
- `GET /api/auth.php` - Trạng thái session
- `POST /api/auth.php?action=login`
- `POST /api/auth.php?action=logout`

### Projects
- `GET /api/projects.php`
- `GET /api/projects.php?archived=1`
- `GET /api/projects.php?id={id}`
- `GET /api/projects.php?code={code}`
- `POST /api/projects.php`
- `PUT /api/projects.php?id={id}`
- `PUT /api/projects.php?id={id}&action=archive`
- `PUT /api/projects.php?id={id}&action=unarchive`
- `DELETE /api/projects.php?id={id}`

### Tasks
- `GET /api/tasks.php?project_id={id}`
- `GET /api/tasks.php?project_id={id}&search=...&status=...&priority=...&assigned_to=...`
- `GET /api/tasks.php?project_id={id}&filters=1`
- `POST /api/tasks.php`
- `PUT /api/tasks.php?id={id}`
- `PUT /api/tasks.php?id={id}&action=status`
- `PUT /api/tasks.php?id={id}&action=claim`
- `PUT /api/tasks.php?id={id}&action=archive`
- `PUT /api/tasks.php?id={id}&action=unarchive`
- `DELETE /api/tasks.php?id={id}`

### Members và Join Requests
- `GET /api/members.php?project_id={id}`
- `POST /api/members.php`
- `DELETE /api/members.php?project_id={id}&user_id={userId}`
- `POST /api/members.php?action=leave`
- `GET /api/join-requests.php?project_id={id}`
- `GET /api/join-requests.php?my=1`
- `POST /api/join-requests.php`
- `PUT /api/join-requests.php?id={id}&action=approve`
- `PUT /api/join-requests.php?id={id}&action=reject`

### Upload
- `POST /api/upload.php?task_id={id}`
- `DELETE /api/upload.php?task_id={id}`
- `GET /api/upload.php?file={filename}`

## 8. Kiểm tra nhanh (lint)

Nếu lệnh `php` không có trong PATH, dùng PHP của XAMPP:

```powershell
& "C:\xampp\php\php.exe" -l includes\task-functions.php
& "C:\xampp\php\php.exe" -l api\tasks.php
```

## 9. Troubleshooting

### Lỗi: Unknown column 'is_archived'
Nguyên nhân: DB cũ chưa có cột archive.

Khắc phục: chạy migration:

`database/migrations/2026-04-02-add-archive-columns.sql`

### Lỗi: 'php' is not recognized
Dùng đúng đường dẫn:

`C:\xampp\php\php.exe`

### Không upload được file
- Kiểm tra dung lượng file <= 5MB
- Kiểm tra định dạng: jpg, jpeg, png, doc, docx, pdf
- Kiểm tra quyền ghi thư mục `uploads/attachments/`

## 10. Tài liệu liên quan

- Đặc tả ứng dụng: `docs/DAC-TA-UNG-DUNG.md`
- Brief chức năng: `docs/BRIEF-00001.md` ... `docs/BRIEF-00004.md`
- Yêu cầu môn học: `docs/Yeu-cau-project.md`
- Báo cáo latex: `report/`

## 11. Lưu ý học thuật

Dự án được xây dựng cho mục đích học tập và báo cáo môn CT214H.
Nếu sử dụng lại cho sản phẩm production, cần bổ sung:
- Secret management
- Hardening security headers
- Logging/monitoring đầy đủ
- Backup và migration strategy chuẩn
- Test tự động và CI/CD
