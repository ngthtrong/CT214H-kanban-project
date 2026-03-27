# 03 - Business Logic Extraction

## Scope
- Mục tiêu: trích xuất luật nghiệp vụ cốt lõi từ use-case diagrams và ERD notes.

## Fact
- Nhóm role chính: Owner và Member trong từng project.
- Rule phân quyền lặp lại xuyên suốt use-cases:
  - Owner-only: mời/xóa member, xóa project, tạo task, xóa task.
  - Owner hoặc assignee: sửa task, di chuyển task.
  - Member: claim task khi `assigned_to = NULL`.
- Rule dữ liệu:
  - `project_members` có ràng buộc unique `(project_id, user_id)`.
  - `tasks.assigned_to` được phép NULL (unassigned).
  - Xóa project kéo theo xóa tasks và project_members (cascade).
- Validation quan trọng:
  - Đăng ký: username/email unique, password mạnh tối thiểu.
  - Upload file: kiểm tra type/size.
  - Search/filter: phân trang tối đa 10 item/trang.

## State-Like Flows
- Task state: `todo -> in_progress -> done` qua drag/drop có kiểm soát quyền.
- Assignment state:
  - Unassigned -> Assigned (owner gán hoặc member claim)
  - Assigned -> Unassigned (owner hủy gán)

## Business Rules (Given/When/Then)
1. Given user là Member trong project, When task có `assigned_to = NULL` và user claim, Then hệ thống gán task cho user hiện tại.
2. Given user không phải Owner, When user thực hiện thao tác xóa task hoặc quản lý members, Then hệ thống từ chối với lỗi quyền.
3. Given project bị xóa bởi Owner, When thao tác xác nhận hoàn tất, Then toàn bộ task/member liên quan bị xóa theo cascade.
4. Given member bị remove khỏi project, When thao tác remove thành công, Then tasks đã gán member đó được set `assigned_to = NULL`.
5. Given user tìm kiếm và lọc đồng thời, When query được thực thi, Then điều kiện tìm kiếm và bộ lọc được áp dụng theo logic AND.

## Assumption
- Các quy tắc trên là design-intent; chưa thể verify bằng mã thực thi/test tự động do thiếu source runtime.
