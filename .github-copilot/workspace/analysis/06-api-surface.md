# 06 - API Surface & Data Model

## Scope
- Mục tiêu: ghi nhận API surface dự kiến từ tài liệu và phân tích mô hình dữ liệu.

## API Surface (Inferred from docs)

## Fact
- Tài liệu đặc tả mô tả nhóm API file-based:
  - `api/auth.php`
  - `api/projects.php`
  - `api/tasks.php`
  - `api/members.php`
  - `api/join-requests.php`
  - `api/upload.php`
- Các endpoint chi tiết chưa được khai báo thành contract chuẩn (OpenAPI/Swagger chưa có).

## Assumption
- Vì chưa có source code runtime, bảng dưới đây là inferred API operations dựa trên use-cases.

| Capability | Method (assumed) | Path (assumed) | Auth | Notes |
|-----------|------------------|----------------|------|-------|
| Register | POST | /api/auth.php?action=register | No | Validate uniqueness + hash password |
| Login | POST | /api/auth.php?action=login | No | Session creation |
| Create project | POST | /api/projects.php | Yes | Owner auto-added vào project_members |
| Update/Delete project | PUT/DELETE | /api/projects.php/{id} | Owner | Delete yêu cầu cascade |
| Invite/Remove member | POST/DELETE | /api/members.php | Owner | Remove member set task assignee NULL |
| Create/Update/Delete task | POST/PUT/DELETE | /api/tasks.php | Owner/Assignee rule | Permission-sensitive |
| Claim task | POST | /api/tasks.php?action=claim | Member | Cần concurrency control |
| Search/filter task | GET | /api/tasks.php?action=search | Member/Owner | Kết hợp query + pagination |
| Upload attachment | POST | /api/upload.php | Owner/Assignee | Validate type/size |

## Database Schema Analysis

## Fact
- ERD hiện có 4 bảng: `users`, `projects`, `project_members`, `tasks`.
- Quan hệ chính:
  - users 1-n projects (owner_id)
  - users n-n projects qua project_members
  - projects 1-n tasks
  - users 1-n tasks (assigned_to, nullable)
- Constraint quan trọng:
  - unique(project_id, user_id) ở project_members
  - task assignment nullable để hỗ trợ claim

## Potential Index Recommendations
- `tasks(project_id, column_status)`
- `tasks(assigned_to)`
- `tasks(priority, due_date)`
- `users(username)`, `users(email)` unique index

## N+1/Query Risk Notes
- Board load có thể cần JOIN users để render assignee avatar/name cho nhiều task.
- Search/filter kết hợp nhiều tiêu chí dễ tạo query nặng nếu thiếu composite index.

## External Dependencies Audit
- Third-party runtime libraries: chưa phát hiện manifest.
- External API/webhook integration: chưa thấy evidence.
- Secrets/config strategy: chưa thấy `.env` runtime.
- Background jobs/schedulers: chưa phát hiện.
