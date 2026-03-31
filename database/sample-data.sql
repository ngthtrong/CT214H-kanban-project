-- =========================================================
-- Team Kanban - Sample Data
-- CT214H Web Programming Final Project
-- =========================================================

USE kanban_db;

-- =========================================================
-- Sample Users (passwords are hashed "password123")
-- =========================================================
INSERT INTO users (username, email, password, full_name, avatar) VALUES
('nguyentrong', 'trong@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Thanh Trọng', NULL),
('huynhan', 'an@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Huỳnh Hồng Ân', NULL),
('ngothinh', 'thinh@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ngô Hưng Thịnh', NULL),
('caohung', 'hung@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cao Tường Hưng', NULL);

-- =========================================================
-- Sample Project
-- =========================================================
INSERT INTO projects (owner_id, project_name, description, project_code) VALUES
(1, 'CT214H Final Project', 'Dự án cuối kỳ môn Lập trình Web - Team Kanban Application', 'CT214H01');

-- =========================================================
-- Project Members (owner + 3 members)
-- =========================================================
INSERT INTO project_members (project_id, user_id, role) VALUES
(1, 1, 'owner'),
(1, 2, 'member'),
(1, 3, 'member'),
(1, 4, 'member');

-- =========================================================
-- Sample Tasks
-- =========================================================
INSERT INTO tasks (project_id, assigned_to, task_title, description, column_status, priority, due_date) VALUES
-- To Do tasks
(1, NULL, 'Setup project structure', 'Tạo cấu trúc thư mục theo đặc tả', 'todo', 'high', '2026-03-25'),
(1, 2, 'Design database schema', 'Thiết kế cơ sở dữ liệu 5 bảng', 'todo', 'high', '2026-03-26'),
(1, NULL, 'Create login page', 'Tạo trang đăng nhập với validation', 'todo', 'medium', '2026-03-28'),

-- In Progress tasks
(1, 1, 'Implement registration', 'Xử lý đăng ký tài khoản mới', 'in_progress', 'high', '2026-03-27'),
(1, 3, 'Create Kanban board CSS', 'Thiết kế giao diện bảng Kanban 3 cột', 'in_progress', 'medium', '2026-03-30'),

-- Done tasks
(1, 4, 'Project initialization', 'Khởi tạo project với composer', 'done', 'low', '2026-03-20'),
(1, 1, 'Write project specification', 'Viết đặc tả ứng dụng chi tiết', 'done', 'high', '2026-03-22');
