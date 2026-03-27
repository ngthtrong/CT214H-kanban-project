# 05 - Test Coverage Assessment

## Scope
- Mục tiêu: đánh giá mức độ bao phủ kiểm thử hiện tại và khoảng trống chất lượng.

## Fact
- Không phát hiện test suite runtime trong workspace (không có cấu trúc test cho PHP app mục tiêu).
- Không phát hiện tool coverage hoặc báo cáo coverage.
- Hiện trạng kiểm chứng chủ yếu dựa trên tài liệu use-case và ERD.

## Coverage Baseline
- Automated unit test coverage: 0% (không có artifact)
- Automated integration/API coverage: 0% (không có artifact)
- E2E coverage: 0% (không có artifact)

## Critical Coverage Gaps
1. Authorization matrix (Owner/Member) cho UC2/UC3/UC4/UC5.
2. Claim task race condition và idempotency cho thao tác nhận task.
3. Search/filter query correctness khi kết hợp nhiều filter.
4. File upload validation và cleanup file khi xóa task.
5. Cascade effects khi xóa project/member.

## Minimum Suggested Test Plan (Given/When/Then)
1. Given task unassigned, When 2 member claim đồng thời, Then chỉ 1 request thành công.
2. Given user không phải owner, When xóa task/member/project, Then response bị từ chối.
3. Given member bị remove, When refresh board, Then task từng gán cho member đó trở thành unassigned.
4. Given keyword + filter status + filter assignee, When search chạy, Then kết quả đúng theo điều kiện AND và phân trang.
5. Given upload file sai loại/kích thước, When submit task, Then hệ thống từ chối file theo rule.

## Assumption
- Coverage sẽ được đo sau khi source code app được thêm đầy đủ và thiết lập test toolchain.
