# 04 - Fragile Zones

## Scope
- Mục tiêu: xác định vùng rủi ro triển khai và vận hành dựa trên artifacts hiện có.

## Risk Matrix

| Zone | Artifact | Risk | Lý do |
|------|----------|------|-------|
| Runtime source missing | Toàn workspace | 🔴 HIGH | Không có source code app để verify behavior, test, security, performance |
| Authorization enforcement | Use-case UC3/UC4/UC5 | 🔴 HIGH | Rule phân quyền dày đặc, dễ lệch giữa UI và backend nếu không có centralized policy |
| Concurrency claim task | UC5 + tasks.assigned_to nullable | 🔴 HIGH | Điều kiện race khi nhiều member claim cùng task chưa được mô tả locking/transaction |
| Search/filter performance | UC6 | 🟡 MEDIUM | Có JOIN + nhiều điều kiện lọc; cần index và query plan thực tế |
| File upload handling | UC4 | 🟡 MEDIUM | Có upload path + validate type/size nhưng chưa có chi tiết chống file abuse |
| Documentation duplication | `.github-copilot/` và `.claude/` | 🟡 MEDIUM | Hai bộ command/agent song song có nguy cơ drift nội dung |
| PlantUML-driven design | `docs/plantuml/` | 🟢 LOW | Có tài liệu tốt giúp trace nghiệp vụ, giảm hiểu nhầm yêu cầu |

## Prioritized Actions
1. P1: Bổ sung source runtime hoặc liên kết đúng repository chứa source trước khi sprint implementation.
2. P1: Định nghĩa authorization layer thống nhất (server-side guard) cho owner/member.
3. P1: Thiết kế transactional claim task để tránh double-claim.
4. P2: Chuẩn hóa security checklist cho upload + validation + permission checks.
5. P2: Thiết lập cơ chế đồng bộ tài liệu giữa `.github-copilot/` và `.claude/`.
