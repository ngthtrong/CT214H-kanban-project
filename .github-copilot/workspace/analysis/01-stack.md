# 01 - Stack Analysis

## Scope
- Mục tiêu: xác định ngôn ngữ, framework, toolchain và khả năng vận hành.
- Nguồn dữ liệu: tài liệu đặc tả, sơ đồ UML/ERD, cấu trúc file trong repo.

## Fact
- Không có manifest runtime trong workspace: không thấy `package.json`, `go.mod`, `requirements.txt`, `composer.json`, `Dockerfile`, `docker-compose.yml`.
- Tài liệu đặc tả xác định stack mục tiêu: PHP + MySQL + HTML/CSS/JavaScript + Bootstrap.
- Repo hiện thiên về artifacts cho workflow agent và tài liệu phân tích/thiết kế.

## Assumption
- Source code triển khai ứng dụng có thể chưa được commit vào repo này hoặc nằm ở branch/repo khác.
- Command chạy local khả thi nhất ở thời điểm hiện tại là PHP built-in server khi có mã nguồn thực tế.

## Runtime & Toolchain Snapshot
- Primary language (target): PHP 8.x
- Frontend (target): HTML5, CSS3, JavaScript, Bootstrap
- Database (target): MySQL 8.x
- Test runner: chưa xác định
- Coverage tool: chưa xác định
- Linter: chưa xác định
- CI/CD: chưa phát hiện pipeline thực thi ứng dụng

## Build/Run Readiness
- Trạng thái: Planning/Documentation-first
- Khả năng chạy end-to-end: chưa đủ điều kiện vì thiếu source app runtime

## Open Questions
- OPEN QUESTION: Source code runtime hiện nằm ở đâu?
- OPEN QUESTION: Có chọn framework backend cụ thể (native PHP/Laravel/khác) hay không?
- OPEN QUESTION: Bộ lệnh chuẩn start/test/coverage/lint của team là gì?
