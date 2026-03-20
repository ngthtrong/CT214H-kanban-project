# BÁO CÁO HỆ THỐNG QUẢN LÝ ĐỒ ÁN NHÓM THEO KANBAN

## 1. Giới thiệu hệ thống
Hệ thống được xây dựng nhằm hỗ trợ quản lý đồ án nhóm trong môi trường lớp học, giúp giảng viên và sinh viên theo dõi tiến độ công việc một cách trực quan thông qua bảng Kanban.

Thay vì quản lý công việc rời rạc qua các nền tảng như email hoặc ứng dụng nhắn tin, hệ thống tập trung toàn bộ thông tin như:
- Công việc
- Tài nguyên
- Bình luận
- Tiến độ dự án

trên một nền tảng duy nhất.

Hệ thống cho phép:
- Giảng viên tạo lớp học và nhóm
- Nhóm sinh viên quản lý dự án
- Theo dõi tiến độ công việc theo Kanban
- Lưu trữ tài nguyên và trao đổi trực tiếp trên từng Task

---

## 2. Kiến trúc tổng thể hệ thống
Hệ thống được chia thành 4 module chính:

1. Module Quản lý Không gian & Nhóm  
2. Module Quản lý Dự án & Bảng Kanban  
3. Module Quản lý Công việc & Tài nguyên  
4. Module Báo cáo & Thống kê  

Mỗi module đều hỗ trợ 4 chức năng cơ bản (CRUD):
- Create
- Read
- Update
- Delete

Các module liên kết với nhau tạo thành một luồng quản lý dự án hoàn chỉnh.

---

## 3. Module 1: Quản lý Không gian & Nhóm
### (Workspace & Team Management)

### Mục đích
Quản lý cấu trúc lớp học và các nhóm sinh viên. Đây là cấp quản lý cao nhất trong hệ thống.

### Chức năng chính
Giảng viên có thể:
- Tạo lớp học (Workspace)
- Tạo các nhóm đồ án
- Thêm sinh viên vào nhóm
- Phân quyền nhóm trưởng

Sinh viên sau khi đăng nhập sẽ chỉ thấy:
- Workspace mà mình tham gia
- Nhóm của mình
- Các dự án thuộc nhóm

### CRUD của module
**Create**
- Tạo Workspace
- Tạo Team
- Thêm sinh viên vào nhóm

**Read**
- Xem danh sách sinh viên
- Xem danh sách nhóm
- Xem thành viên từng nhóm

**Update**
- Đổi tên nhóm
- Chuyển sinh viên giữa các nhóm
- Thay đổi nhóm trưởng

**Delete**
- Xóa nhóm
- Lưu trữ Workspace

---

## 4. Module 2: Quản lý Dự án & Bảng Kanban
### (Project & Kanban Board Management)

### Mục đích
Quản lý dự án và bảng Kanban để theo dõi tiến độ công việc.

### Cấu trúc Kanban
Ví dụ các cột:
- To-Do
- Doing
- Review
- Done

### CRUD của module
**Create**
- Tạo dự án
- Tạo cột Kanban

**Read**
- Xem thông tin dự án
- Xem bảng Kanban

**Update**
- Đổi tên dự án
- Đổi tên cột
- Thay đổi thứ tự cột

**Delete**
- Xóa dự án
- Xóa cột

---

## 5. Module 3: Quản lý Công việc & Tài nguyên
### (Task & Resource Management)

### Mục đích
Quản lý các công việc cụ thể trong dự án.

### Thông tin Task
Một Task bao gồm:
- Tiêu đề
- Mô tả
- Người thực hiện
- Deadline
- Trạng thái
- Sub-task
- Bình luận
- File đính kèm

### CRUD của module
**Create**
- Tạo Task
- Tạo Sub-task
- Thêm comment
- Upload file

**Read**
- Xem chi tiết Task
- Xem tài nguyên

**Update**
- Chỉnh sửa Task
- Thay đổi deadline
- Kéo thả Task giữa các cột

**Delete**
- Xóa Task
- Xóa comment
- Xóa file

---

## 6. Module 4: Báo cáo & Thống kê
### (Report & Analytics Management)

### Mục đích
Cung cấp cái nhìn tổng quan về tiến độ dự án và hiệu suất làm việc của các thành viên.

### Chức năng chính
- Thống kê số lượng Task theo trạng thái
- Tính phần trăm hoàn thành dự án
- Thống kê số Task của từng thành viên
- Phát hiện Task trễ deadline

### Ví dụ báo cáo
- Dự án hoàn thành 70%
- Thành viên A: 10 Task
- Thành viên B: 5 Task

### CRUD của module
**Create**
- Tạo báo cáo theo thời điểm

**Read**
- Xem dashboard thống kê
- Xem chi tiết báo cáo

**Update**
- Làm mới dữ liệu thống kê

**Delete**
- Xóa báo cáo cũ

---

## 7. Luồng hoạt động của hệ thống

Giảng viên tạo Workspace  
→ Tạo nhóm sinh viên  
→ Nhóm tạo dự án  
→ Tạo bảng Kanban  
→ Tạo Task  
→ Sinh viên thực hiện công việc  
→ Cập nhật tiến độ  
→ Xem báo cáo thống kê  

---

## 8. Công nghệ dự kiến sử dụng

### Backend
- Spring Boot hoặc PHP

### Database
- MySQL hoặc PostgreSQL

### Frontend
- React hoặc Vue

### File Storage
- Local hoặc Cloud Storage

---

## 9. Kết luận

Hệ thống quản lý đồ án nhóm theo Kanban giúp tăng hiệu quả làm việc nhóm và khả năng theo dõi tiến độ dự án.

Việc thay thế module Thông báo bằng module Báo cáo & Thống kê giúp hệ thống:
- Đơn giản hơn trong triển khai
- Phù hợp với phạm vi môn học
- Vẫn đảm bảo tính thực tiễn và giá trị sử dụng cao

Hệ thống vẫn đáp ứng đầy đủ các chức năng quản lý cần thiết thông qua các module CRUD rõ ràng và hợp lý.

