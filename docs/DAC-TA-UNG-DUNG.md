# ĐẶC TẢ ỨNG DỤNG QUẢN LÝ CÔNG VIỆC KANBAN

## 1. TỔNG QUAN DỰ ÁN

### 1.1. Thông tin cơ bản

- **Tên dự án:** Team Kanban - Hệ thống quản lý dự án nhóm
- **Nhóm thực hiện:**
  - Nguyễn Thanh Trọng - B2305615
  - Huỳnh Hồng Ân - B2306657
  - Ngô Hưng Thịnh - B2303904
  - Cao Tường Hưng - B2303873
- **Thời gian thực hiện:** 1 tháng (20/03/2026 - 17/04/2026)
- **Công nghệ:** PHP, MySQL, HTML5, CSS3, JavaScript

### 1.2. Mô tả tổng quan

Ứng dụng Team Kanban là một công cụ quản lý dự án nhóm dành cho sinh viên, giúp tổ chức và theo dõi các nhiệm vụ học tập theo phương pháp Kanban. Hệ thống cho phép người dùng tạo project, mời thành viên tham gia (qua email/username hoặc qua mã project), phân công và theo dõi tiến độ công việc một cách trực quan.

### 1.3. Đối tượng người dùng

**Sinh viên** - có thể đảm nhận 2 vai trò:

**1. Project Owner (Chủ project):**

- Tạo project mới
- Mời và quản lý members (thêm/xóa thành viên)
- Tạo, chỉnh sửa, xóa tasks
- Gán tasks cho members cụ thể
- Xem toàn bộ công việc của project

**2. Project Member (Thành viên):**

- Nhận lời mời tham gia project
- Xem tất cả tasks trong project
- Tự nhận (claim) các tasks chưa được phân công
- Cập nhật trạng thái tasks được gán cho mình
- Upload file đính kèm cho tasks của mình

**Lưu ý:** Mỗi user có thể là Owner của nhiều project và đồng thời là Member của các project khác.

---

## 2. KIẾN TRÚC HỆ THỐNG

### 2.1. Kiến trúc tổng quan

```
┌─────────────────────────────────────────┐
│         PRESENTATION LAYER              │
│  (HTML5, CSS3, JavaScript, Bootstrap)   │
└─────────────────────────────────────────┘
                  ↕
┌─────────────────────────────────────────┐
│        APPLICATION LAYER                │
│              (PHP 8.x)                  │
└─────────────────────────────────────────┘
                  ↕
┌─────────────────────────────────────────┐
│          DATA LAYER                     │
│           (MySQL 8.x)                   │
└─────────────────────────────────────────┘
```

### 2.2. Cấu trúc thư mục

```
kanban-project/
├── index.php                 # Trang chủ/Dashboard
├── login.php                 # Trang đăng nhập
├── register.php              # Trang đăng ký
├── logout.php                # Xử lý đăng xuất
├── profile.php               # Quản lý profile
├── project.php               # Hiển thị project (board Kanban)
├── task.php                  # Chi tiết task
├── search.php                # Tìm kiếm tasks
├── members.php               # Quản lý members của project
├── join-project.php          # Trang nhập mã project để join
├── join-requests.php         # Quản lý yêu cầu tham gia (owner)
├── css/
│   ├── style.css            # CSS chính
│   ├── kanban.css           # CSS cho Kanban board
│   └── responsive.css       # CSS responsive
├── js/
│   ├── main.js              # JavaScript chính
│   ├── drag-drop.js         # Drag & drop tasks
│   ├── task-modal.js        # Modal thêm/sửa task
│   ├── member-modal.js      # Modal mời members
│   └── search-filter.js     # Tìm kiếm và lọc
├── images/
│   └── (logo, icons, backgrounds)
├── uploads/
│   └── (file đính kèm của tasks)
├── includes/
│   ├── db-connect.php       # Kết nối database
│   ├── config.php           # Cấu hình hệ thống
│   ├── session.php          # Quản lý session
│   └── functions.php        # Hàm dùng chung
├── api/
│   ├── auth.php             # API xác thực
│   ├── projects.php         # API quản lý projects
│   ├── tasks.php            # API quản lý tasks
│   ├── members.php          # API quản lý members
│   ├── join-requests.php    # API quản lý yêu cầu tham gia
│   └── upload.php           # API upload file
└── database/
    ├── schema.sql           # Cấu trúc database
    └── sample-data.sql      # Dữ liệu mẫu
```

---

## 3. THIẾT KẾ CƠ SỞ DỮ LIỆU

### 3.1. Sơ đồ ERD

```
                              ┌──────────────────┐
                              │  project_members │
                              ├──────────────────┤
                    ┌─────────┤ member_id PK     │
                    │         │ project_id FK    │
                    │         │ user_id FK       │
                    │         │ role             │
                    │         │ joined_at        │
                    │         └──────────────────┘
                    │                   │
                    │                   │
                    │         ┌─────────────────────┐
                    │         │ project_join_requests│
                    │         ├─────────────────────┤
                    │    ┌────┤ request_id PK       │
                    │    │    │ project_id FK       │
                    │    │    │ user_id FK          │
                    │    │    │ status              │
                    │    │    │ requested_at        │
                    │    │    │ responded_at        │
                    │    │    └─────────────────────┘
                    │    │              │
┌─────────────┐    │    │    ┌──────────────┐         ┌─────────────────┐
│   users     │1   │  * │  * │   projects   │1      * │     tasks       │
├─────────────┤◄───┴────┴────┤──────────────┤◄────────┤─────────────────┤
│ user_id PK  │              │ project_id PK│         │ task_id PK      │
│ username    │◄────┐        │ owner_id FK  │         │ project_id FK   │
│ email       │     │        │ project_name │    ┌────┤ assigned_to FK  │
│ password    │     │        │ description  │    │    │ task_title      │
│ full_name   │     │        │ project_code │    │    │ description     │
│ avatar      │     │        │ created_at   │    │    │ column_status   │
│ created_at  │     │        │ updated_at   │    │    │ priority        │
└─────────────┘     │        └──────────────┘    │    │ due_date        │
                    │                             │    │ attachment_path │
                    │                             │    │ created_at      │
                    └─────────────────────────────┘    │ updated_at      │
                        (assigned_to)                  └─────────────────┘
```

### 3.2. Mô tả các bảng

#### Bảng `users` (Người dùng)

| Trường   | Kiểu dữ liệu | Ràng buộc                 | Mô tả                        |
| ---------- | --------------- | --------------------------- | ------------------------------ |
| user_id    | INT             | PRIMARY KEY, AUTO_INCREMENT | ID người dùng               |
| username   | VARCHAR(50)     | UNIQUE, NOT NULL            | Tên đăng nhập              |
| email      | VARCHAR(100)    | UNIQUE, NOT NULL            | Email                          |
| password   | VARCHAR(255)    | NOT NULL                    | Mật khẩu (đã hash)         |
| full_name  | VARCHAR(100)    | NOT NULL                    | Họ và tên                   |
| avatar     | VARCHAR(255)    | NULL                        | Đường dẫn ảnh đại diện |
| created_at | TIMESTAMP       | DEFAULT CURRENT_TIMESTAMP   | Ngày tạo                     |

#### Bảng `projects` (Dự án Kanban)

| Trường      | Kiểu dữ liệu | Ràng buộc                         | Mô tả                                 |
| ------------- | --------------- | ----------------------------------- | --------------------------------------- |
| project_id    | INT             | PRIMARY KEY, AUTO_INCREMENT         | ID project                              |
| owner_id      | INT             | FOREIGN KEY, NOT NULL               | ID chủ project                         |
| project_name  | VARCHAR(100)    | NOT NULL                            | Tên project                            |
| description   | TEXT            | NULL                                | Mô tả project                         |
| project_code  | VARCHAR(10)     | UNIQUE, NOT NULL                    | Mã project để join (8 ký tự random) |
| created_at    | TIMESTAMP       | DEFAULT CURRENT_TIMESTAMP           | Ngày tạo                              |
| updated_at    | TIMESTAMP       | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Ngày cập nhật                        |

**Foreign Keys:**

- `owner_id` REFERENCES `users(user_id)` ON DELETE CASCADE

#### Bảng `project_members` (Thành viên của Project)

| Trường   | Kiểu dữ liệu         | Ràng buộc                 | Mô tả                |
| ---------- | ----------------------- | --------------------------- | ---------------------- |
| member_id  | INT                     | PRIMARY KEY, AUTO_INCREMENT | ID member record       |
| project_id | INT                     | FOREIGN KEY, NOT NULL       | ID project             |
| user_id    | INT                     | FOREIGN KEY, NOT NULL       | ID người dùng       |
| role       | ENUM('owner', 'member') | NOT NULL, DEFAULT 'member'  | Vai trò trong project |
| joined_at  | TIMESTAMP               | DEFAULT CURRENT_TIMESTAMP   | Ngày tham gia         |

**Foreign Keys:**

- `project_id` REFERENCES `projects(project_id)` ON DELETE CASCADE
- `user_id` REFERENCES `users(user_id)` ON DELETE CASCADE

**Unique Constraint:**

- UNIQUE(`project_id`, `user_id`) - Mỗi user chỉ tham gia 1 lần vào 1 project

#### Bảng `project_join_requests` (Yêu cầu tham gia Project)

| Trường    | Kiểu dữ liệu                      | Ràng buộc                 | Mô tả                                           |
| ----------- | ------------------------------------ | --------------------------- | ------------------------------------------------- |
| request_id  | INT                                  | PRIMARY KEY, AUTO_INCREMENT | ID yêu cầu tham gia                             |
| project_id  | INT                                  | FOREIGN KEY, NOT NULL       | ID project muốn tham gia                        |
| user_id     | INT                                  | FOREIGN KEY, NOT NULL       | ID người gửi yêu cầu                          |
| status      | ENUM('pending', 'approved', 'rejected') | NOT NULL, DEFAULT 'pending' | Trạng thái yêu cầu                            |
| requested_at| TIMESTAMP                            | DEFAULT CURRENT_TIMESTAMP   | Thời gian gửi yêu cầu                        |
| responded_at| TIMESTAMP                            | NULL                        | Thời gian owner phản hồi                     |

**Foreign Keys:**

- `project_id` REFERENCES `projects(project_id)` ON DELETE CASCADE
- `user_id` REFERENCES `users(user_id)` ON DELETE CASCADE

**Unique Constraint:**

- UNIQUE(`project_id`, `user_id`, `status`) - Mỗi user chỉ có 1 yêu cầu pending cho 1 project

#### Bảng `tasks` (Công việc)

| Trường        | Kiểu dữ liệu                     | Ràng buộc                         | Mô tả                                          |
| --------------- | ----------------------------------- | ----------------------------------- | ------------------------------------------------ |
| task_id         | INT                                 | PRIMARY KEY, AUTO_INCREMENT         | ID task                                          |
| project_id      | INT                                 | FOREIGN KEY, NOT NULL               | ID project chứa task                            |
| assigned_to     | INT                                 | FOREIGN KEY, NULL                   | ID người được gán task (NULL = chưa gán) |
| task_title      | VARCHAR(200)                        | NOT NULL                            | Tiêu đề task                                  |
| description     | TEXT                                | NULL                                | Mô tả chi tiết                                |
| column_status   | ENUM('todo', 'in_progress', 'done') | NOT NULL, DEFAULT 'todo'            | Trạng thái cột                                |
| priority        | ENUM('low', 'medium', 'high')       | DEFAULT 'medium'                    | Mức độ ưu tiên                              |
| due_date        | DATE                                | NULL                                | Hạn hoàn thành                                |
| attachment_path | VARCHAR(255)                        | NULL                                | Đường dẫn file đính kèm                   |
| created_at      | TIMESTAMP                           | DEFAULT CURRENT_TIMESTAMP           | Ngày tạo                                       |
| updated_at      | TIMESTAMP                           | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Ngày cập nhật                                 |

**Foreign Keys:**

- `project_id` REFERENCES `projects(project_id)` ON DELETE CASCADE
- `assigned_to` REFERENCES `users(user_id)` ON DELETE SET NULL

---

## 4. PHÂN CHIA MODULE VÀ PHÂN CÔNG

### Module 1: Quản lý Người dùng & Giao diện (User Authentication & UI)

**Trách nhiệm:** Nguyễn Thanh Trọng - B2305615
**Công nghệ:** HTML5, CSS3, PHP

**Chức năng:**

- Thiết kế giao diện tổng thể (layout, header, footer, navigation)
- Responsive design cho mobile/tablet
- Đăng ký tài khoản mới
- Đăng nhập / Đăng xuất
- Quản lý profile (xem, chỉnh sửa thông tin, upload avatar)
- Validation form phía client và server

**File đảm nhận:**

- `register.php`, `login.php`, `logout.php`, `profile.php`
- `css/style.css`, `css/responsive.css`
- `api/auth.php`
- `includes/session.php`

---

### Module 2: Quản lý Project & Members (Project & Member Management)

**Trách nhiệm:** Huỳnh Hồng Ân - B2306657
**Công nghệ:** JavaScript, PHP

**Chức năng:**

- Tạo project mới (tự động là owner, tự động tạo mã project 8 ký tự)
- Xem danh sách projects (cả projects sở hữu và tham gia)
- Chỉnh sửa thông tin project (tên, mô tả) - chỉ owner
- Xóa project (cùng tất cả tasks và members) - chỉ owner
- Hiển thị project với 3 cột: To Do, In Progress, Done
- Hiển thị và copy mã project để chia sẻ
- Mời members vào project (nhập email hoặc username)
- Tham gia project bằng mã (nhập mã project, gửi yêu cầu)
- Xem và duyệt yêu cầu tham gia (owner: approve/reject)
- Xem danh sách members của project
- Xóa members khỏi project - chỉ owner

**File đảm nhận:**

- `index.php` (dashboard - danh sách projects)
- `project.php` (hiển thị project chi tiết)
- `members.php` (quản lý members)
- `join-project.php` (form nhập mã project)
- `join-requests.php` (quản lý yêu cầu tham gia)
- `css/kanban.css`
- `api/projects.php`
- `api/members.php`
- `api/join-requests.php`
- `js/main.js` (phần quản lý projects)
- `js/member-modal.js` (modal mời members)
- `js/join-request-modal.js` (modal duyệt yêu cầu)

---

### Module 3: Quản lý Task (Task Management)

**Trách nhiệm:** Ngô Hưng Thịnh - B2303904
**Công nghệ:** JavaScript, PHP

**Chức năng:**

- Tạo task mới (form modal)
- Gán task cho member cụ thể hoặc để trống (unassigned) - chỉ owner
- Tự nhận (claim) task chưa được gán - member
- Xem chi tiết task
- Chỉnh sửa task (title, description, priority, due_date, assigned_to)
  - Owner: chỉnh sửa tất cả tasks
  - Member: chỉ sửa tasks được gán cho mình
- Xóa task - chỉ owner
- Di chuyển task giữa các cột (drag & drop)
  - Member: chỉ di chuyển tasks của mình
- Upload file đính kèm cho task
- Download/xóa file đính kèm
- Hiển thị avatar/tên người được gán trên task card

**File đảm nhận:**

- `task.php` (chi tiết task)
- `js/drag-drop.js`
- `js/task-modal.js`
- `api/tasks.php`
- `api/upload.php`

---

### Module 4: Tìm kiếm, Lọc & Database (Search, Filter & Database)

**Trách nhiệm:** Cao Tường Hưng - B2303873
**Công nghệ:** MySQL, PHP, JavaScript

**Chức năng:**

- Thiết kế và tạo cơ sở dữ liệu (4 bảng)
- Tìm kiếm task theo từ khóa (title, description)
- Lọc tasks theo:
  - Trạng thái (To Do, In Progress, Done)
  - Mức độ ưu tiên (Low, Medium, High)
  - Người được gán (Unassigned, hoặc theo member cụ thể)
  - Hạn hoàn thành (quá hạn, hôm nay, tuần này, tháng này)
- Phân trang (pagination) kết quả
- Tối ưu query database (indexes, joins)

**File đảm nhận:**

- `search.php`
- `js/search-filter.js`
- `database/schema.sql`
- `database/sample-data.sql`
- `includes/db-connect.php`
- `includes/functions.php`

---

## 5. USE CASES

### 5.1. Use Case Diagram

```
                    ┌─────────────────────┐
                    │   Sinh viên         │
                    │   (User)            │
                    └──────────┬──────────┘
                               │
        ┌──────────────────────┼──────────────────────────────┐
        │                      │                              │
        ▼                      ▼                              ▼
┌───────────────┐      ┌──────────────┐              ┌──────────────┐
│ UC1: Đăng ký/ │      │ UC2: Quản lý │              │ UC3: Quản lý │
│ Đăng nhập     │      │   Projects   │              │   Members    │
└───────────────┘      └──────┬───────┘              └──────┬───────┘
                              │                              │
                              └──────────┬───────────────────┘
                                         │
                         ┌───────────────┼───────────────┬──────────────┐
                         ▼               ▼               ▼              ▼
                  ┌──────────────┐ ┌──────────┐ ┌──────────────┐ ┌─────────────┐
                  │ UC4: Quản lý │ │UC5: Gán &│ │ UC6: Tìm kiếm│ │UC7: Tham gia│
                  │    Tasks     │ │Nhận Tasks│ │  và Lọc      │ │Project (Mã) │
                  └──────────────┘ └──────────┘ └──────────────┘ └─────────────┘
```

### 5.2. Chi tiết Use Cases

---

#### **UC1: Đăng ký và Đăng nhập**

**Mô tả:** Người dùng tạo tài khoản mới hoặc đăng nhập vào hệ thống

**Actor:** Sinh viên

**Precondition:** Không có

**Postcondition:** Người dùng được xác thực và truy cập hệ thống

**Main Flow - Đăng ký:**

1. Người dùng truy cập trang đăng ký
2. Nhập thông tin: username, email, password, full_name
3. Hệ thống validate dữ liệu:
   - Kiểm tra username/email đã tồn tại chưa
   - Kiểm tra độ mạnh password (min 6 ký tự)
   - Validate format email
4. Hệ thống mã hóa password và lưu vào DB
5. Hiển thị thông báo đăng ký thành công
6. Chuyển hướng đến trang đăng nhập

**Main Flow - Đăng nhập:**

1. Người dùng nhập username/email và password
2. Hệ thống kiểm tra thông tin đăng nhập
3. Nếu đúng, tạo session và chuyển đến dashboard
4. Nếu sai, hiển thị thông báo lỗi

**Alternative Flow:**

- A1: Username/email đã tồn tại → Thông báo lỗi, yêu cầu nhập lại
- A2: Password không đủ mạnh → Hiển thị yêu cầu password
- A3: Thông tin đăng nhập sai → Hiển thị lỗi, cho phép thử lại

---

#### **UC2: Quản lý Project**

**Mô tả:** Người dùng tạo, xem, sửa, xóa Kanban projects

**Actor:** Sinh viên (đã đăng nhập)

**Precondition:** Người dùng đã đăng nhập

**Postcondition:** Project được tạo/cập nhật/xóa thành công

**Main Flow - Tạo Project:**

1. Người dùng click nút "Tạo Project mới" trên dashboard
2. Hệ thống hiển thị form nhập thông tin:
   - Tên project (bắt buộc)
   - Mô tả (tùy chọn)
3. Người dùng nhập thông tin và submit
4. Hệ thống validate và lưu vào DB
5. Tự động thêm user vào bảng `project_members` với role='owner'
6. Hiển thị project mới trong danh sách
7. Chuyển đến trang project

**Main Flow - Xem Project:**

1. Người dùng click vào project từ dashboard
2. Hệ thống kiểm tra quyền (owner hoặc member)
3. Load project với 3 cột: To Do, In Progress, Done
4. Hiển thị tất cả tasks trong các cột tương ứng
5. Hiển thị avatar của người được gán trên mỗi task card

**Main Flow - Sửa Project:**

1. Người dùng click nút "Chỉnh sửa" trên project
2. Hệ thống kiểm tra: chỉ owner mới được sửa
3. Hiển thị form với dữ liệu hiện tại
4. Người dùng chỉnh sửa và submit
5. Hệ thống cập nhật DB và hiển thị thông báo

**Main Flow - Xóa Project:**

1. Người dùng click nút "Xóa project"
2. Hệ thống kiểm tra: chỉ owner mới được xóa
3. Hiển thị xác nhận: "Xóa project sẽ xóa tất cả tasks và members. Bạn có chắc?"
4. Nếu xác nhận, xóa project (CASCADE xóa tasks và members)
5. Chuyển về dashboard

**Alternative Flow:**

- A1: Tên project trống → Hiển thị lỗi validation
- A2: User không phải owner cố sửa/xóa → Hiển thị lỗi "Không có quyền"
- A3: Người dùng hủy xóa → Không thực hiện thao tác

---

#### **UC3: Quản lý Members**

**Mô tả:** Owner mời thành viên vào project, xem và quản lý danh sách members

**Actor:** Sinh viên (Owner của project)

**Precondition:**

- Người dùng đã đăng nhập
- Người dùng là owner của project

**Postcondition:** Member được thêm/xóa khỏi project

**Main Flow - Mời Member:**

1. Owner click nút "Mời thành viên" trên trang project
2. Hệ thống hiển thị modal form nhập:
   - Email hoặc username của người muốn mời
3. Owner nhập thông tin và submit
4. Hệ thống tìm kiếm user trong DB:
   - Kiểm tra user có tồn tại không
   - Kiểm tra user đã là member của project chưa
5. Nếu hợp lệ, thêm vào bảng `project_members` với role='member'
6. Hiển thị thông báo thành công
7. User xuất hiện trong danh sách members

**Main Flow - Xem danh sách Members:**

1. Owner hoặc member click vào tab/icon "Members"
2. Hệ thống hiển thị danh sách tất cả members với:
   - Avatar, tên, email
   - Vai trò (Owner/Member)
   - Ngày tham gia
   - Số lượng tasks đang được gán (nếu có)

**Main Flow - Xóa Member:**

1. Owner click nút "Xóa" bên cạnh member (không thể xóa chính mình)
2. Hệ thống hiển thị xác nhận: "Xóa member sẽ hủy gán tất cả tasks của họ. Tiếp tục?"
3. Nếu xác nhận:
   - Xóa member khỏi `project_members`
   - Set `assigned_to = NULL` cho tất cả tasks của member đó
4. Hiển thị thông báo và cập nhật danh sách

**Alternative Flow:**

- A1: Email/username không tồn tại → "Không tìm thấy user"
- A2: User đã là member → "User đã tham gia project"
- A3: User cố mời chính mình → "Bạn đã là owner"
- A4: Member (không phải owner) cố mời người khác → "Không có quyền"

---

#### **UC4: Quản lý Task**

**Mô tả:** Người dùng tạo, xem, sửa, xóa, di chuyển tasks và upload file

**Actor:** Sinh viên (đã đăng nhập, là owner hoặc member của project)

**Precondition:** Người dùng đã tham gia ít nhất 1 project

**Postcondition:** Task được tạo/cập nhật/xóa/di chuyển thành công

**Main Flow - Tạo Task (Owner):**

1. Owner click nút "+" trên một cột (To Do/In Progress/Done)
2. Hệ thống hiển thị modal form nhập thông tin:
   - Tiêu đề (bắt buộc)
   - Mô tả (tùy chọn)
   - Gán cho (dropdown list members, hoặc "Unassigned")
   - Mức độ ưu tiên: Low/Medium/High
   - Hạn hoàn thành (tùy chọn)
   - Upload file đính kèm (tùy chọn)
3. Owner nhập thông tin và submit
4. Hệ thống validate, upload file (nếu có), lưu vào DB
5. Task mới xuất hiện trong cột tương ứng với avatar người được gán

**Main Flow - Xem Chi tiết Task:**

1. Người dùng click vào task card
2. Hệ thống hiển thị trang chi tiết với đầy đủ thông tin:
   - Title, description, status, priority, due_date
   - Người được gán (avatar + tên)
   - File đính kèm (nếu có) với nút download
   - Ngày tạo, ngày cập nhật

**Main Flow - Sửa Task:**

1. Người dùng click nút "Chỉnh sửa" trên task
2. Hệ thống kiểm tra quyền:
   - Owner: có thể sửa mọi task
   - Member: chỉ sửa task được gán cho mình
3. Hiển thị form với dữ liệu hiện tại
4. Người dùng chỉnh sửa (bao gồm thay đổi người được gán) và submit
5. Hệ thống cập nhật DB

**Main Flow - Xóa Task:**

1. Người dùng click nút "Xóa" trên task
2. Hệ thống kiểm tra: chỉ owner mới được xóa
3. Hiển thị xác nhận
4. Nếu xác nhận, xóa task và file đính kèm (nếu có)

**Main Flow - Di chuyển Task (Drag & Drop):**

1. Người dùng kéo (drag) task card
2. Hệ thống kiểm tra quyền:
   - Owner: di chuyển mọi task
   - Member: chỉ di chuyển tasks được gán cho mình
3. Thả (drop) vào cột khác
4. Hệ thống cập nhật `column_status` trong DB bằng AJAX
5. Task xuất hiện ở vị trí mới

**Main Flow - Upload File:**

1. Người dùng chọn file từ máy (trong form tạo/sửa task)
2. Hệ thống validate file:
   - Kiểm tra định dạng cho phép (pdf, doc, docx, jpg, png, max 5MB)
   - Kiểm tra kích thước file
3. Upload file vào thư mục `uploads/`
4. Lưu đường dẫn vào DB

**Alternative Flow:**

- A1: Tiêu đề task trống → Lỗi validation
- A2: Member cố sửa task của người khác → "Không có quyền"
- A3: Member cố xóa task → "Chỉ owner mới có thể xóa"
- A4: File không đúng định dạng/quá lớn → Hiển thị lỗi, yêu cầu chọn lại
- A5: Upload file thất bại → Vẫn tạo task nhưng thông báo lỗi upload

---

#### **UC5: Gán và Nhận Task**

**Mô tả:** Owner gán task cho members, members tự nhận tasks chưa được phân công

**Actor:** Sinh viên (Owner hoặc Member)

**Precondition:** Project có ít nhất 2 members và có tasks

**Postcondition:** Task được gán cho user cụ thể

**Main Flow - Gán Task (Owner):**

1. Owner mở form sửa task hoặc tạo task mới
2. Trong phần "Assigned to", chọn member từ dropdown
3. Submit form
4. Hệ thống cập nhật `assigned_to` trong DB
5. Task card hiển thị avatar của member được gán
6. (Optional) Gửi thông báo cho member

**Main Flow - Tự nhận Task (Member):**

1. Member xem project board
2. Nhìn thấy task có label "Unassigned" (assigned_to = NULL)
3. Click nút "Nhận task" hoặc icon trên task card
4. Hệ thống cập nhật `assigned_to = current_user_id`
5. Task card hiển thị avatar của member
6. Thông báo "Bạn đã nhận task thành công"

**Main Flow - Hủy gán Task (Owner):**

1. Owner mở form sửa task
2. Chọn "Unassigned" trong dropdown "Assigned to"
3. Submit form
4. Hệ thống set `assigned_to = NULL`
5. Task trở về trạng thái chưa được gán

**Alternative Flow:**

- A1: Member cố gán task cho người khác → "Chỉ owner mới có quyền gán"
- A2: Task đã được gán cho người khác và member cố nhận → "Task đã được gán"
- A3: Member nhận task của chính mình (đã gán) → "Bạn đã được gán task này"

---

#### **UC6: Tìm kiếm và Lọc Task**

**Mô tả:** Người dùng tìm kiếm tasks theo từ khóa và lọc theo nhiều tiêu chí

**Actor:** Sinh viên (đã đăng nhập)

**Precondition:** Người dùng đã có ít nhất 1 task

**Postcondition:** Hiển thị danh sách tasks phù hợp với tiêu chí

**Main Flow - Tìm kiếm:**

1. Người dùng nhập từ khóa vào ô tìm kiếm (trên dashboard hoặc project)
2. Hệ thống tìm kiếm trong `task_title` và `description` (LIKE %keyword%)
3. Hiển thị kết quả theo thời gian thực (AJAX)
4. Phân trang nếu kết quả > 10 items

**Main Flow - Lọc:**

1. Người dùng chọn tiêu chí lọc:
   - **Theo trạng thái:** To Do / In Progress / Done / Tất cả
   - **Theo mức độ ưu tiên:** Low / Medium / High / Tất cả
   - **Theo người được gán:**
     - Unassigned (chưa gán)
     - Assigned to me (tasks của tôi)
     - Assigned to [Member name] (theo member cụ thể)
     - Tất cả
   - **Theo hạn hoàn thành:**
     - Quá hạn (due_date < today)
     - Hôm nay (due_date = today)
     - Tuần này (due_date trong 7 ngày tới)
     - Tháng này (due_date trong 30 ngày tới)
     - Tất cả
2. Hệ thống thực hiện query với WHERE conditions và JOIN với bảng users
3. Hiển thị kết quả lọc

**Main Flow - Kết hợp Tìm kiếm + Lọc:**

1. Người dùng vừa nhập từ khóa vừa chọn bộ lọc
2. Hệ thống áp dụng cả điều kiện tìm kiếm AND điều kiện lọc
3. Hiển thị kết quả phù hợp

**Main Flow - Phân trang:**

1. Nếu kết quả > 10 tasks
2. Hiển thị navigation: Previous, 1, 2, 3, ..., Next
3. Mỗi trang hiển thị tối đa 10 tasks
4. Click số trang để chuyển trang (AJAX hoặc page reload)

**Alternative Flow:**

- A1: Không có kết quả → Hiển thị "Không tìm thấy task phù hợp"
- A2: Lọc trả về tất cả tasks → Hiển thị toàn bộ danh sách

---

#### **UC7: Tham gia Project bằng Mã**

**Mô tả:** Người dùng nhập mã project để gửi yêu cầu tham gia, owner duyệt yêu cầu

**Actor:** Sinh viên (đã đăng nhập)

**Precondition:**
- Người dùng đã đăng nhập
- Có project với mã code hợp lệ

**Postcondition:** Người dùng trở thành member sau khi được owner duyệt

**Main Flow - Gửi yêu cầu tham gia:**

1. Người dùng truy cập trang "Tham gia Project" hoặc click nút "Join Project"
2. Hệ thống hiển thị form nhập mã project
3. Người dùng nhập mã project (8 ký tự) và submit
4. Hệ thống kiểm tra:
   - Mã project có tồn tại không
   - User đã là member của project chưa
   - User đã gửi yêu cầu pending chưa
5. Nếu hợp lệ, tạo record trong bảng `project_join_requests` với status='pending'
6. Hiển thị thông báo: "Yêu cầu đã được gửi. Vui lòng đợi owner phê duyệt"
7. (Optional) Gửi notification cho owner

**Main Flow - Xem danh sách yêu cầu (Owner):**

1. Owner truy cập trang project hoặc notification center
2. Hệ thống hiển thị badge/counter số yêu cầu pending
3. Owner click vào "Yêu cầu tham gia" (Join Requests)
4. Hệ thống hiển thị danh sách các yêu cầu pending với:
   - Avatar, tên, email của người yêu cầu
   - Thời gian gửi yêu cầu
   - Nút "Chấp nhận" và "Từ chối"

**Main Flow - Duyệt yêu cầu (Owner):**

1. Owner click nút "Chấp nhận" hoặc "Từ chối" bên cạnh yêu cầu
2. Nếu "Chấp nhận":
   - Cập nhật `status='approved'` và `responded_at=NOW()` trong `project_join_requests`
   - Thêm user vào bảng `project_members` với role='member'
   - (Optional) Gửi thông báo cho user: "Bạn đã được chấp nhận vào project [tên]"
3. Nếu "Từ chối":
   - Cập nhật `status='rejected'` và `responded_at=NOW()`
   - (Optional) Gửi thông báo cho user
4. Yêu cầu biến mất khỏi danh sách pending
5. Hiển thị thông báo thành công

**Main Flow - Xem mã project (Owner/Member):**

1. Người dùng vào trang chi tiết project
2. Click nút "Chia sẻ Project" hoặc icon share
3. Hệ thống hiển thị modal với:
   - Mã project (có thể copy)
   - Hướng dẫn: "Chia sẻ mã này để mời người khác tham gia"
   - Nút "Copy mã"
4. Người dùng copy mã và chia sẻ qua bất kỳ kênh nào (email, chat, ...)

**Alternative Flow:**

- A1: Mã project không tồn tại → "Mã project không hợp lệ"
- A2: User đã là member của project → "Bạn đã tham gia project này"
- A3: User đã gửi yêu cầu pending → "Bạn đã gửi yêu cầu. Vui lòng đợi phê duyệt"
- A4: User cố join project mà mình là owner → "Bạn là owner của project này"
- A5: Owner từ chối yêu cầu → User có thể gửi lại yêu cầu sau (tùy chọn thiết kế)

---

## 6. TÍNH NĂNG CHỨC NĂNG

### 6.1. Danh sách tính năng

| STT | Tính năng                   | Mô tả                                              | Module   |
| --- | ----------------------------- | ---------------------------------------------------- | -------- |
| 1   | Đăng ký tài khoản        | Tạo tài khoản mới với username, email, password | Module 1 |
| 2   | Đăng nhập                  | Xác thực người dùng, tạo session               | Module 1 |
| 3   | Đăng xuất                  | Hủy session, đăng xuất khỏi hệ thống          | Module 1 |
| 4   | Quản lý Profile             | Xem, chỉnh sửa thông tin cá nhân, upload avatar | Module 1 |
| 5   | Tạo Project                  | Tạo Kanban project mới (tự động làm owner)     | Module 2 |
| 6   | Xem danh sách Projects       | Hiển thị projects owned và joined                 | Module 2 |
| 7   | Chỉnh sửa Project           | Cập nhật tên và mô tả project (chỉ owner)     | Module 2 |
| 8   | Xóa Project                  | Xóa project và tất cả tasks/members (chỉ owner) | Module 2 |
| 9   | Hiển thị Project Kanban     | Hiển thị với 3 cột: To Do, In Progress, Done     | Module 2 |
| 10  | Mời Members                  | Mời user khác vào project bằng email/username    | Module 2 |
| 11  | Xem danh sách Members        | Hiển thị tất cả members của project             | Module 2 |
| 12  | Xóa Members                  | Xóa member khỏi project (chỉ owner)               | Module 2 |
| 13  | Tạo mã Project              | Tự động tạo mã 8 ký tự khi tạo project       | Module 2 |
| 14  | Tham gia Project bằng mã    | User nhập mã project để gửi yêu cầu tham gia  | Module 2 |
| 15  | Duyệt yêu cầu tham gia    | Owner xem và duyệt/từ chối yêu cầu           | Module 2 |
| 16  | Chia sẻ mã Project          | Xem và copy mã project để chia sẻ              | Module 2 |
| 17  | Tạo Task                     | Tạo task mới trong project                         | Module 3 |
| 18  | Xem chi tiết Task            | Hiển thị đầy đủ thông tin task và assignee   | Module 3 |
| 19  | Chỉnh sửa Task              | Cập nhật thông tin task (theo quyền)             | Module 3 |
| 20  | Xóa Task                     | Xóa task khỏi project (chỉ owner)                 | Module 3 |
| 21  | Gán Task                     | Owner gán task cho member cụ thể                  | Module 3 |
| 22  | Tự nhận Task                | Member tự claim task chưa được gán             | Module 3 |
| 23  | Di chuyển Task               | Drag & drop task giữa các cột (theo quyền)       | Module 3 |
| 24  | Upload File                   | Upload file đính kèm cho task                     | Module 3 |
| 25  | Download File                 | Tải xuống file đính kèm                         | Module 3 |
| 26  | Tìm kiếm Task               | Tìm task theo từ khóa                             | Module 4 |
| 27  | Lọc theo trạng thái        | Lọc tasks theo cột (To Do, In Progress, Done)      | Module 4 |
| 28  | Lọc theo mức độ ưu tiên | Lọc tasks theo Low/Medium/High                      | Module 4 |
| 29  | Lọc theo assignee            | Lọc unassigned, assigned to me, theo member         | Module 4 |
| 30  | Lọc theo hạn hoàn thành   | Lọc tasks quá hạn, hôm nay, tuần/tháng này    | Module 4 |
| 31  | Phân trang                   | Hiển thị tasks theo trang (10 tasks/page)          | Module 4 |
| 32  | Form Validation               | Validate dữ liệu client-side và server-side       | Module 1 |
| 33  | Responsive Design             | Giao diện tương thích mobile/tablet              | Module 1 |
| 34  | Authorization                 | Kiểm soát quyền owner/member cho mọi thao tác   | Tất cả |

### 6.2. Tính năng bonus (nếu còn thời gian)

- Thống kê số lượng tasks theo trạng thái (biểu đồ tròn)
- Thống kê workload của từng member
- Sắp xếp tasks theo ngày tạo, hạn hoàn thành, mức độ ưu tiên
- Đánh dấu task quan trọng (star/favorite)
- Notification khi được gán task mới
- Activity log (lịch sử thay đổi task)
- Dark mode

---

## 7. YÊU CẦU KỸ THUẬT

### 7.1. Frontend

- **HTML5:** Semantic markup, accessibility
- **CSS3:** Flexbox, Grid, animations, responsive design
- **JavaScript (ES6+):**
  - DOM manipulation
  - AJAX/Fetch API
  - Drag & Drop API
  - Form validation
  - Event handling
- **Bootstrap 5** (optional): Để nhanh chóng xây dựng UI

### 7.2. Backend

- **PHP 8.x:**
  - OOP (classes, objects)
  - PDO cho database operations
  - Session management
  - File upload handling
  - Password hashing (password_hash, password_verify)
  - Input validation và sanitization
  - Prepared statements (SQL injection prevention)

### 7.3. Database

- **MySQL 8.x:**
  - Normalized design (3NF)
  - Foreign key constraints
  - Indexes cho performance
  - Transactions (nếu cần)

### 7.4. Security

- Password hashing với bcrypt
- Prepared statements để chống SQL injection
- CSRF protection (token)
- XSS prevention (htmlspecialchars)
- Session hijacking prevention
- File upload validation (type, size, malware)
- Input sanitization

### 7.5. Performance

- AJAX cho các thao tác không cần reload page
- Lazy loading images
- Minify CSS/JS (production)
- Database indexes
- Caching (nếu cần)

---

## 8. WORKFLOW PHÁT TRIỂN

### 8.1. Phân công chi tiết theo tuần

#### **Tuần 1 (20/03 - 26/03): Setup & Core Features**

- **Tất cả thành viên:**

  - Setup môi trường (XAMPP/WAMP, Git, IDE)
  - Tạo repository Git và cấu trúc thư mục
- **Trọng (Module 1):**

  - Thiết kế layout chung (header, footer, navigation)
  - Tạo trang đăng ký + validation
  - Tạo trang đăng nhập + session
- **Ân (Module 2):**

  - Tạo dashboard (danh sách projects)
  - Tạo trang project với 3 cột tĩnh
  - Implement tạo/sửa/xóa project + tạo mã project tự động
  - Implement mời members cơ bản
- **Thịnh (Module 3):**

  - Tạo form modal thêm/sửa task
  - Implement CRUD tasks (chưa có drag-drop)
  - Thêm dropdown "Assigned to" trong form task
- **Hưng (Module 4):**

  - Thiết kế database schema (4 bảng)
  - Tạo `schema.sql` và `sample-data.sql`
  - Setup `db-connect.php` và `functions.php`
  - Implement search cơ bản

#### **Tuần 2 (27/03 - 02/04): Advanced Features**

- **Trọng:**

  - Hoàn thiện responsive design
  - Tạo trang profile và upload avatar
  - Tối ưu CSS, fix UI bugs
- **Ân:**

  - Tối ưu hiển thị project (real-time update)
  - Hiển thị avatar assignee trên task cards
  - Implement xem/xóa members
  - Implement tính năng join project bằng mã
  - Implement quá trình duyệt yêu cầu tham gia (approve/reject)
  - Tạo trang chia sẻ mã project
  - Integrate với Module 3 cho task display
- **Thịnh:**

  - Implement drag & drop tasks với permission check
  - Implement "Claim task" button cho unassigned tasks
  - Implement upload file đính kèm
  - Implement download/xóa file
  - Permission check: owner vs member
- **Hưng:**

  - Implement filter (status, priority, assignee, due_date)
  - Implement pagination
  - Tối ưu query database (indexes, joins)

#### **Tuần 3 (03/04 - 09/04): Integration & Testing**

- **Tất cả thành viên:**
  - Integration testing: đảm bảo các module hoạt động mượt mà
  - Testing permission/authorization
  - Bug fixing
  - Security testing
  - Performance optimization

#### **Tuần 4 (10/04 - 17/04): Documentation & Presentation**

- **Tất cả thành viên:**
  - Viết báo cáo dự án
  - Tạo slide thuyết trình
  - Chuẩn bị demo (kịch bản: tạo project, mời member, gán tasks)
  - Submit trước deadline (17/04)

### 8.2. Quy trình làm việc

1. **Version Control (Git):**

   - Branch cho mỗi module: `feature/module-1`, `feature/module-2`, ...
   - Merge vào `develop` sau khi hoàn thành
   - Merge `develop` → `main` khi release
2. **Code Review:**

   - Mỗi thành viên review code của người khác
   - Sử dụng Pull Request trên GitHub
3. **Testing:**

   - Unit testing cho các hàm quan trọng
   - Integration testing cho luồng chức năng
   - User acceptance testing
4. **Meetings:**

   - Daily standup (15 phút): tiến độ, vướng mắc
   - Weekly review (1 tiếng): demo, feedback, điều chỉnh

---

## 9. TIÊU CHÍ ĐÁNH GIÁ

### 9.1. Checklist hoàn thành

- [ ] Database: ít nhất 3 bảng có liên kết ✅ (có 5 bảng: users, projects, project_members, project_join_requests, tasks)
- [ ] CRUD operations: đầy đủ Create, Read, Update, Delete ✅
- [ ] Search & Filter: tìm kiếm và lọc theo nhiều tiêu chí ✅ (bao gồm lọc theo assignee)
- [ ] File Upload: upload file đính kèm cho tasks ✅
- [ ] Form Validation: client-side và server-side ✅
- [ ] Pagination: phân trang kết quả ✅
- [ ] Responsive Design: mobile/tablet friendly ✅
- [ ] Security: chống SQL injection, XSS, CSRF ✅
- [ ] Authorization: phân quyền owner/member ✅ (tính năng nâng cao)
- [ ] Join Request System: tham gia project qua mã + duyệt yêu cầu ✅ (tính năng nâng cao)

### 9.2. Tiêu chí chấm điểm (dự kiến)

- **Chức năng (40%):** Đầy đủ tính năng, hoạt động đúng
- **Giao diện (20%):** Đẹp, UX tốt, responsive
- **Code quality (15%):** Clean code, structure tốt, comments
- **Database design (10%):** Chuẩn hóa, hiệu quả
- **Security (10%):** Xử lý tốt các lỗ hổng bảo mật
- **Báo cáo & Thuyết trình (5%):** Trình bày rõ ràng, demo mượt

---

## 10. RỦI RO VÀ GIẢI PHÁP

| Rủi ro                             | Mức độ   | Giải pháp                                                     |
| ----------------------------------- | ----------- | --------------------------------------------------------------- |
| Thành viên không đủ thời gian | Cao         | Phân công rõ ràng, họp thường xuyên, hỗ trợ lẫn nhau |
| Xung đột code khi merge           | Trung bình | Sử dụng Git đúng cách, code review                         |
| Không đáp ứng deadline          | Trung bình | Ưu tiên tính năng core, có plan backup                     |
| Bug phát sinh cuối dự án        | Cao         | Testing sớm, thường xuyên, dự phòng thời gian fix bug    |
| Thiếu kinh nghiệm PHP/MySQL       | Trung bình | Học từ tài liệu, tutorial, hỏi giảng viên                |

---

## 11. TÀI LIỆU THAM KHẢO

### 11.1. Công nghệ

- [PHP Manual](https://www.php.net/manual/en/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MDN Web Docs](https://developer.mozilla.org/) - HTML, CSS, JavaScript
- [Bootstrap Documentation](https://getbootstrap.com/docs/)

### 11.2. Kanban Methodology

- Trello, Jira (để tham khảo UI/UX)
- Kanban board best practices

### 11.3. Security

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- PHP Security Best Practices

---

## 12. PHỤ LỤC

### 12.1. API Endpoints (Tham khảo)

#### Authentication

- `POST /api/auth.php?action=register` - Đăng ký
- `POST /api/auth.php?action=login` - Đăng nhập
- `POST /api/auth.php?action=logout` - Đăng xuất

#### Projects

- `GET /api/projects.php` - Lấy danh sách projects (owned + joined)
- `GET /api/projects.php?id={project_id}` - Lấy chi tiết project
- `POST /api/projects.php` - Tạo project mới
- `PUT /api/projects.php?id={project_id}` - Cập nhật project (owner only)
- `DELETE /api/projects.php?id={project_id}` - Xóa project (owner only)

#### Members

- `GET /api/members.php?project_id={project_id}` - Lấy danh sách members
- `POST /api/members.php` - Mời member vào project (owner only)
  - Body: `{project_id, email/username}`
- `DELETE /api/members.php?id={member_id}` - Xóa member (owner only)

#### Join Requests

- `GET /api/join-requests.php?project_id={project_id}` - Lấy danh sách yêu cầu tham gia (owner only)
- `POST /api/join-requests.php` - Gửi yêu cầu tham gia project
  - Body: `{project_code}`
- `PUT /api/join-requests.php?id={request_id}` - Duyệt/từ chối yêu cầu (owner only)
  - Body: `{status: 'approved' | 'rejected'}`
- `GET /api/projects.php?id={project_id}&action=get_code` - Lấy mã project để chia sẻ

#### Tasks

- `GET /api/tasks.php?project_id={project_id}` - Lấy tasks của project
- `GET /api/tasks.php?id={task_id}` - Lấy chi tiết task
- `POST /api/tasks.php` - Tạo task mới
  - Body: `{project_id, task_title, description, assigned_to, priority, due_date}`
- `PUT /api/tasks.php?id={task_id}` - Cập nhật task
- `DELETE /api/tasks.php?id={task_id}` - Xóa task (owner only)
- `PATCH /api/tasks.php?id={task_id}` - Di chuyển task (update column_status)
- `POST /api/tasks.php?action=claim&id={task_id}` - Member tự nhận task

#### Upload

- `POST /api/upload.php` - Upload file
  - Body: multipart/form-data với file

### 12.2. Sample Data

Tạo ít nhất:

- **3 users:**
  - User 1: Owner của Project 1 và 2
  - User 2: Member của Project 1, Owner của Project 3
  - User 3: Member của Project 1 và 2
- **3 projects** với members:
  - Project 1: 3 members (1 owner, 2 members), project_code: "ABC12345"
  - Project 2: 2 members (1 owner, 1 member), project_code: "XYZ67890"
  - Project 3: 1 member (chỉ owner), project_code: "DEF54321"
- **Join requests mẫu:**
  - User 3 đã gửi yêu cầu join vào Project 3 (status: pending)
  - User 2 đã được duyệt join vào Project 1 (status: approved)
- **20 tasks phân bổ:**
  - 50% assigned, 50% unassigned
  - Phân đều 3 cột
  - Các mức độ ưu tiên khác nhau
  - Một số có file đính kèm
  - Một số quá hạn, một số sắp đến hạn

---

## KẾT LUẬN

Đặc tả này cung cấp roadmap chi tiết cho dự án Team Kanban - một hệ thống quản lý dự án nhóm với khả năng collaborative. Với sự phân công rõ ràng và thời gian hợp lý, nhóm hoàn toàn có thể hoàn thành dự án đúng hạn với chất lượng tốt.

**Điểm nổi bật của dự án:**

- ✅ Đáp ứng đầy đủ yêu cầu môn học (5 bảng liên kết, CRUD, search/filter, upload, validation, pagination, responsive)
- ✅ Tính năng nâng cao: Collaborative work (mời members qua email/username + join qua mã project)
- ✅ Join Request System: Người dùng tự gửi yêu cầu tham gia, owner duyệt
- ✅ Authorization system (phân quyền owner/member)
- ✅ User-friendly: tự nhận tasks, hiển thị assignee trên cards, chia sẻ mã project
- ✅ Phù hợp với thời gian 1 tháng và 4 thành viên

**Lưu ý cuối:**

- Luôn backup code thường xuyên (Git)
- Ưu tiên tính năng core trước:
  1. Authentication + Projects CRUD
  2. Tasks CRUD + Members management
  3. Assign/Claim tasks
  4. Search/Filter + Permissions
  5. Drag & drop, upload files
- Testing và security phải được thực hiện song song với development
- **Quan trọng:** Test kỹ phân quyền owner/member để đảm bảo security
- Communication là chìa khóa thành công

**Liên hệ hỗ trợ:**

- Họp nhóm: hàng ngày lúc [thời gian cố định]
- Group chat: [Zalo/Discord/Telegram]
- Repository: [GitHub link]

---

**Ngày tạo:** 20/03/2026
**Phiên bản:** 2.0 (Updated with collaborative features)
**Người soạn:** Nhóm CT214H - Kanban Project
