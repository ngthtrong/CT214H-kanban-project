# PROJECT-PROFILE.md

> File này được điền tự động bởi `/detect-stack` và `/discover-codebase`, hoặc có thể điền thủ công.
> Các agent khác (Developer, Tester, Doc Sync) sẽ đọc file này để biết cách chạy tests, coverage, và smoke test.

## Stack

- **Language**: PHP 8.x (FACT: từ tài liệu đặc tả)
- **Framework**: Native PHP backend + Bootstrap frontend (FACT: từ tài liệu đặc tả)
- **Database**: MySQL 8.x (FACT: từ tài liệu đặc tả)
- **Test runner**: Chưa xác định (không có cấu hình test trong repo hiện tại)
- **Coverage tool**: Chưa xác định
- **Linter**: Chưa xác định

## Commands

```yaml
start: "php -S localhost:8000 -t ."   # ASSUMPTION: local dev PHP server
stop: "Ctrl+C"                         # manual stop
test: "N/A - chưa có test automation"
coverage: "N/A - chưa có coverage automation"
lint: "N/A - chưa có lint automation"
health_check_url: "N/A"
```

## Smoke Test Scenarios (≤ 10)

<!-- Liệt kê các critical user flow cần test trên live environment -->

1. User có thể đăng ký tài khoản mới.
2. User có thể đăng nhập và đăng xuất.
3. Owner có thể tạo project và mời thành viên.
4. Member có thể claim task chưa được gán.
5. User được phép tìm kiếm/lọc task theo từ khóa và trạng thái.

## Fragile Zones Summary

| Zone | File | Risk | Lý do |
|------|------|------|-------|
| Documentation-only stage | Toàn repo | 🟡 MEDIUM | Chưa có source runtime/test config nên chưa verify được khả năng chạy thật |

## Tech Debt Register

| ID | Mô tả | Ảnh hưởng | Priority |
|----|-------|-----------|----------|
| TD-001 | Thiếu source code ứng dụng trong workspace hiện tại | Không thể chạy build/test/lint tự động | P1 |
| TD-002 | Thiếu test runner và coverage pipeline | Không đo được chất lượng regression | P1 |

## External Dependencies

- **Third-party APIs**: Chưa xác định
- **Secrets management**: Chưa xác định
- **Background jobs**: Chưa xác định

---
*File này được tạo bởi `/setup-project` và cập nhật bởi `/detect-stack`, `/discover-codebase`*
