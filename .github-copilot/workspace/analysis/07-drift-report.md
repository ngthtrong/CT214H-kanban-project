# 07 - Documentation Drift Report

## Scope

- Mục tiêu: so sánh tài liệu hiện có với hiện trạng workspace để phát hiện drift.

## Drift Table

| Tài liệu                            | Tuyên bố                                                                                | Thực tế workspace                                                                                | Mức độ drift |
| ------------------------------------- | ----------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | --------------- |
| DAC-TA-UNG-DUNG.md                    | Có cấu trúc source app hoàn chỉnh (`index.php`, `api/*.php`, `database/*.sql`) | Repo hiện chưa chứa source runtime tương ứng                                                 | 🔴 HIGH         |
| DAC-TA-UNG-DUNG.md                    | Mô tả bảng `project_join_requests` và cột `project_code` trong phần DB          | ERD PlantUML hiện chỉ mô tả 4 bảng và không có `project_join_requests`, `project_code` | 🔴 HIGH         |
| docs/plantuml/README.md               | Nêu rõ flow UC đã sẵn sàng để triển khai                                         | Chưa có mã triển khai/test để xác nhận behavior                                            | 🟡 MEDIUM       |
| Yeu-cau-project.md                    | Deliverable yêu cầu complete source code                                                | Workspace hiện là tài liệu + workflow scaffolding                                              | 🟡 MEDIUM       |
| `.github-copilot/` và `.claude/` | Hai hệ command/agent song song                                                           | Có nguy cơ lệch nội dung nếu cập nhật không đồng bộ                                     | 🟡 MEDIUM       |

## Fact

- Các drift HIGH tập trung ở chênh lệch giữa design docs và artifacts runtime.
- Drift MEDIUM chủ yếu là trạng thái “planned nhưng chưa implemented”.

## Assumption

- Một phần source code chưa được triển khai repo hiện tại chỉ gồm các tài liệu đặt tả

## Suggested Remediation

1. Chốt source-of-truth cho runtime app (repo/branch/path) và liên kết vào workspace.
2. Đồng bộ ERD với phần mô tả DB (quyết định giữ 4 bảng hay mở rộng join requests/project_code).
3. Tạo API contract tối thiểu (OpenAPI hoặc bảng endpoint chuẩn hóa) để giảm ambiguity.
4. Thiết lập checklist đồng bộ hai bộ command `.github-copilot/` và `.claude/`.
