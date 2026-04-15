# Opening Script (Sections 1-3) - Bilingual

Scope of this script:
- Section 1: Introduction & Objectives
- Section 2: System Architecture
- Section 3: Database Design (detailed)

Speaker role:
- Opening presenter
- Handover to next presenter at end of Section 3

Format:
- VI: Vietnamese line to speak
- EN: English equivalent line

---

## 0) Opening Greeting (20-30s)

VI:
Kính chào thầy và các bạn. Nhóm em là Team Kanban, thực hiện đề tài Kanban Project Model Management cho học phần CT214H Web Programming. Em là người mở đầu, hôm nay em sẽ trình bày từ mục 1 đến mục 3, gồm phần giới thiệu, kiến trúc hệ thống và thiết kế cơ sở dữ liệu.

EN:
Good morning lecturer and everyone. We are Team Kanban, and our topic is Kanban Project Model Management for CT214H Web Programming. I will present the opening part, covering Sections 1 to 3: introduction, system architecture, and database design.

---

## 1) Section 1 - Introduction & Objectives (1.5-2 minutes)

### 1.1 Kanban Project Model Overview

VI:
Đề tài của nhóm em xây dựng một website quản lý công việc theo mô hình Kanban. Luồng công việc được trực quan qua 3 trạng thái chính: To Do, In Progress và Done. Cách tổ chức này giúp nhóm theo dõi tiến độ rõ ràng, dễ nhìn thấy bottleneck và tối ưu quy trình thực hiện.

EN:
Our project builds a web-based task management system following the Kanban model. The workflow is visualized through three core states: To Do, In Progress, and Done. This structure helps teams track progress clearly, detect bottlenecks, and optimize execution flow.

### 1.2 Project Objectives

VI:
Về mục tiêu, nhóm em tập trung vào 3 điểm chính:
Thứ nhất, đảm bảo xác thực người dùng an toàn và quản lý hồ sơ cá nhân.
Thứ hai, hỗ trợ tạo workspace theo vai trò, tức là cộng tác có phân quyền.
Thứ ba, triển khai đầy đủ vòng đời task trên Kanban board.

EN:
For objectives, we focus on three main goals:
First, secure authentication and profile management.
Second, role-aware workspace collaboration.
Third, full task lifecycle implementation on the Kanban board.

### 1.3 Target Users and Roles

VI:
Trong mỗi project có 2 vai trò chính.
Owner có toàn quyền với thiết lập dự án và quản lý thành viên.
Member tập trung theo dõi tiến độ và cập nhật các task được giao.
Việc tách rõ vai trò giúp hệ thống an toàn hơn và hạn chế thao tác ngoài phạm vi cho phép.

EN:
Each project has two main roles.
The Owner has full authority over project settings and team management.
The Member focuses on progress tracking and updating assigned tasks.
This clear role separation improves security and prevents out-of-scope operations.

### Transition to Section 2

VI:
Sau phần giới thiệu, em chuyển sang kiến trúc hệ thống để thấy cách nhóm em tổ chức code và phân tách trách nhiệm giữa các lớp.

EN:
After this introduction, I will move to system architecture to show how we organized the codebase and separated responsibilities across layers.

---

## 2) Section 2 - System Architecture (1-1.5 minutes)

### 2.1 Architecture Style

VI:
Hệ thống của nhóm em đi theo hướng PHP-based Modular Monolith. Nghĩa là triển khai trong một codebase thống nhất, nhưng chia thành các module rõ ràng để dễ bảo trì và mở rộng.

EN:
Our system follows a PHP-based Modular Monolith architecture. It runs as one unified codebase, but with clearly separated modules for maintainability and extensibility.

VI:
Lý do chọn mô hình này là để cân bằng giữa tính đơn giản khi triển khai đồ án và tính chặt chẽ trong phân tách trách nhiệm.

EN:
We chose this model to balance implementation simplicity for coursework with strict separation of responsibilities.

### 2.2 Main Structure

VI:
Trong cấu trúc chính:
- Thư mục api chứa các JSON endpoints.
- Thư mục includes chứa business logic và kết nối cơ sở dữ liệu.
- Thư mục js xử lý tương tác AJAX và hành vi giao diện.
- Các file ở thư mục gốc là các trang giao diện người dùng.

EN:
In the main structure:
- The api folder contains JSON endpoints.
- The includes folder contains business logic and database connection modules.
- The js folder handles AJAX interactions and UI behavior.
- Root-level files are user-facing pages.

VI:
Nhờ cách chia này, frontend, API và domain logic không bị trộn lẫn, nên khi debug hoặc mở rộng tính năng sẽ nhanh hơn.

EN:
With this separation, frontend, API, and domain logic are not mixed together, so debugging and feature extension become faster.

### Transition to Section 3

VI:
Tiếp theo là phần quan trọng nhất trong mở đầu của em: thiết kế cơ sở dữ liệu, vì đây là nền tảng đảm bảo đúng nghiệp vụ và an toàn dữ liệu.

EN:
Next is the most important part of my opening: database design, because it is the foundation for business correctness and data safety.

---

## 3) Section 3 - Database Design (Detailed, 3-4 minutes)

### 3.1 Design Goal

VI:
Mục tiêu thiết kế database của nhóm em là đảm bảo 3 yếu tố: đúng nghiệp vụ cộng tác, toàn vẹn dữ liệu, và truy vấn hiệu quả khi số lượng task tăng.

EN:
Our database design aims for three factors: collaboration-oriented business correctness, strong data integrity, and efficient querying as task volume grows.

### 3.2 Entity Overview (Explain each table)

VI:
Hệ thống gồm 5 thực thể cốt lõi:
- Users: lưu thông tin tài khoản và hồ sơ.
- Projects: lưu không gian làm việc, owner và mã project.
- Project Members: bảng nối thể hiện user nào thuộc project nào, kèm role.
- Join Requests: lưu luồng xin tham gia và trạng thái duyệt.
- Tasks: lưu các đơn vị công việc với status, priority, due date và assignee.

EN:
The system has five core entities:
- Users: stores account and profile information.
- Projects: stores workspace data, owner, and project code.
- Project Members: junction table mapping users to projects with roles.
- Join Requests: stores join workflow and approval states.
- Tasks: stores work items with status, priority, due date, and assignee.

VI:
Điểm đáng chú ý là Project Members giúp chuẩn hóa quan hệ nhiều-nhiều giữa users và projects, đồng thời lưu thêm ngữ nghĩa vai trò.

EN:
A key point is that Project Members normalizes the many-to-many relation between users and projects while preserving role semantics.

### 3.3 Relationship Summary

VI:
Về quan hệ dữ liệu:
- Một user có thể sở hữu nhiều project.
- Một project có nhiều member và một member có thể tham gia nhiều project.
- Một project có nhiều join request.
- Một project có nhiều task.
- Một user có thể được assign nhiều task, và task cũng có thể để unassigned.

EN:
For data relationships:
- One user can own multiple projects.
- One project has many members, and one member can join multiple projects.
- One project can have many join requests.
- One project can have many tasks.
- One user can be assigned many tasks, and tasks can also stay unassigned.

### 3.4 Integrity and Constraint Semantics

VI:
Phần này rất quan trọng để bảo vệ dữ liệu:
- Mỗi bảng dùng primary key tự tăng để định danh duy nhất.
- Các trường nghiệp vụ quan trọng có unique constraint, ví dụ username, email, project_code.
- Bảng project_members có unique(project_id, user_id) để chặn trùng thành viên.
- Bảng join_requests có unique(project_id, user_id, status) để tránh yêu cầu trùng trạng thái.

EN:
This part is crucial for data protection:
- Every table uses an auto-increment primary key for unique identity.
- Important business fields have unique constraints, such as username, email, and project_code.
- Project_members uses unique(project_id, user_id) to prevent duplicate membership.
- Join_requests uses unique(project_id, user_id, status) to avoid duplicate requests in the same state.

VI:
Về khóa ngoại:
- Các quan hệ phụ thuộc project dùng ON DELETE CASCADE để không tạo dữ liệu mồ côi.
- Riêng tasks.assigned_to dùng ON DELETE SET NULL để giữ lại task khi user bị xóa khỏi vai trò assignee.

EN:
For foreign keys:
- Project-dependent relations use ON DELETE CASCADE to avoid orphan records.
- tasks.assigned_to uses ON DELETE SET NULL so tasks are preserved even when an assignee is removed.

### 3.5 Domain Constraints and Lifecycle

VI:
Để đảm bảo dữ liệu luôn hợp lệ, các trường trạng thái dùng ENUM:
- role: owner/member
- join request status: pending/approved/rejected
- task status: todo/in_progress/done
- priority: low/medium/high

EN:
To enforce valid domain values, status-like fields use ENUM:
- role: owner/member
- join request status: pending/approved/rejected
- task status: todo/in_progress/done
- priority: low/medium/high

VI:
Ngoài ra, hệ thống có lifecycle archive cho project và task bằng is_archived và archived_at. Cách này giúp ẩn dữ liệu khỏi luồng làm việc chính mà vẫn bảo toàn lịch sử để truy vết hoặc phục hồi.

EN:
In addition, the system supports archive lifecycle for projects and tasks using is_archived and archived_at. This hides data from active workflows while preserving history for traceability and restoration.

### 3.6 Why This Database Design Is Practical

VI:
Tóm lại, thiết kế database này giải quyết đồng thời ba bài toán:
- Đúng nghiệp vụ cộng tác theo vai trò.
- Chặt chẽ về toàn vẹn dữ liệu.
- Thuận lợi cho các tính năng phía trên như search/filter/pagination ở các phần sau của bài trình bày.

EN:
In summary, this database design solves three problems at once:
- Correct role-based collaboration behavior.
- Strong data integrity guarantees.
- Good support for upper-layer features such as search/filter/pagination, which will be presented in the next part.

---

## 4) Handover To Next Presenter (10-15s)

VI:
Trên đây là phần mở đầu từ mục 1 đến mục 3 của em. Tiếp theo, nhóm em xin chuyển sang mục 4 - Key Features để đi sâu vào luồng chức năng và triển khai thực tế trong code.

EN:
That concludes my opening presentation for Sections 1 to 3. Next, we will move to Section 4 - Key Features to dive deeper into functional flows and practical implementation in code.
