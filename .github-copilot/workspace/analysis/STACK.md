# Stack Detection Report

**Ngày**: 2026-03-27
**Detected by**: Agent Codebase Analyst

## Evidence Summary

### Fact
- Không tìm thấy file manifest/toolchain runtime trong workspace (`package.json`, `go.mod`, `requirements.txt`, `composer.json`, `Dockerfile`, `docker-compose.yml`).
- Tài liệu [DAC-TA-UNG-DUNG.md](../../../DAC-TA-UNG-DUNG.md) mô tả stack mục tiêu:
  - Language: PHP 8.x
  - Database: MySQL 8.x
  - Frontend: HTML5, CSS3, JavaScript, Bootstrap

### Assumption
- Repo hiện ở giai đoạn planning/documentation, source code ứng dụng chưa được commit.

## Language & Runtime
- **Primary Language**: PHP (từ tài liệu đặc tả)
- **Version**: 8.x (từ tài liệu đặc tả)

## Framework
- **Web Framework**: Chưa xác định framework backend cụ thể (tài liệu mô tả kiến trúc PHP thuần)
- **UI Framework**: Bootstrap
- **ORM/Database**: Chưa thấy ORM; database mục tiêu là MySQL 8.x

## Testing
- **Test Runner**: Chưa xác định (không có file cấu hình test)
- **Coverage Tool**: Chưa xác định

## Build & Deploy
- **Build Tool**: Chưa xác định
- **Container**: Không tìm thấy Dockerfile / docker-compose
- **CI/CD**: Không tìm thấy workflow CI/CD trong mã nguồn ứng dụng

## Recommended Commands

```yaml
start: "php -S localhost:8000 -t ."   # ASSUMPTION: chạy local dev server PHP
test: "N/A - chưa có test automation"
coverage: "N/A - chưa có coverage automation"
lint: "N/A - chưa có lint automation"
```

## Open Questions
- OPEN QUESTION: Source code runtime (PHP app) đang ở nhánh khác hay chưa được thêm vào repo?
- OPEN QUESTION: Team dự kiến dùng framework backend nào (native PHP, Laravel, hoặc khác)?
- OPEN QUESTION: Bộ lệnh chuẩn để start/test/lint cho môi trường dev là gì?
