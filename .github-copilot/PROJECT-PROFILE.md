# PROJECT-PROFILE.md

> File này được điền tự động bởi `/detect-stack` và `/discover-codebase`, hoặc có thể điền thủ công.
> Các agent khác (Developer, Tester, Doc Sync) sẽ đọc file này để biết cách chạy tests, coverage, và smoke test.

## Stack

- **Language**: PHP 8.x (FACT: từ tài liệu đặc tả)
- **Framework**: Native PHP backend + Bootstrap frontend (FACT: từ tài liệu đặc tả)
- **Database**: MySQL 8.x (FACT: từ tài liệu đặc tả)
- **Test runner**: PHPUnit 11.x (SELECTED)
- **Coverage tool**: PCOV (installed as PHP extension)
- **Linter**: PHP_CodeSniffer + PHPMD (PSR-12 standard)

## Commands

```yaml
# Development server (XAMPP)
start: "Start Apache + MySQL from XAMPP Control Panel"
stop: "Stop Apache + MySQL from XAMPP Control Panel"
health_check_url: "http://localhost"

# Testing & Quality
test: "C:\\xampp\\php\\php.exe vendor\\bin\\phpunit"
coverage: "C:\\xampp\\php\\php.exe vendor\\bin\\phpunit --coverage-text --coverage-percentage"
coverage_gate: 80    # minimum threshold for CI/CD
lint: "C:\\xampp\\php\\php.exe vendor\\bin\\phpcs src && C:\\xampp\\php\\php.exe vendor\\bin\\phpmd src text cleancode,codesize,naming"
lint_fix: "C:\\xampp\\php\\php.exe vendor\\bin\\phpcbf src"

# Setup (run once after git clone)
setup: "composer install && composer dump-autoload -o"
test_install: "composer require --dev phpunit/phpunit squizlabs/php_codesniffer phpmd/phpmd"
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
| Runtime source missing | Toàn repo | 🔴 HIGH | Không có source app để xác thực nghiệp vụ, bảo mật, hiệu năng |
| Authorization enforcement | docs/plantuml/use-cases/uc3-member-management.puml; docs/plantuml/use-cases/uc4-task-management.puml; docs/plantuml/use-cases/uc5-assign-claim-tasks.puml | 🔴 HIGH | Nhiều rule phân quyền owner/member, dễ lệch giữa UI và backend nếu không có guard tập trung |
| Concurrency claim task | docs/plantuml/use-cases/uc5-assign-claim-tasks.puml | 🔴 HIGH | Nguy cơ race condition khi nhiều member claim cùng lúc |
| Search/filter performance | docs/plantuml/use-cases/uc6-search-filter.puml | 🟡 MEDIUM | Query kết hợp nhiều điều kiện + JOIN cần index thực tế |
| Documentation duplication | .github-copilot/ và .claude/ | 🟡 MEDIUM | Hai bộ command song song có nguy cơ drift |

## Tech Debt Register

| ID | Mô tả | Ảnh hưởng | Priority |
|----|-------|-----------|----------|
| TD-001 | Thiếu source code ứng dụng trong workspace hiện tại | Không thể chạy build/test/lint tự động | P1 |
| TD-002 | Thiếu test runner và coverage pipeline | Không đo được chất lượng regression | P1 |
| TD-003 | Chưa có API contract chuẩn hóa (OpenAPI/Swagger) | Tăng ambiguity khi implement và test | P1 |
| TD-004 | Chưa có chiến lược xử lý race condition khi claim task | Có thể gây dữ liệu gán task không nhất quán | P1 |

## External Dependencies

- **Third-party APIs**: Chưa xác định
- **Secrets management**: Chưa xác định
- **Background jobs**: Chưa xác định

---
*File này được tạo bởi `/setup-project` và cập nhật bởi `/detect-stack`, `/discover-codebase`*
