# PlantUML Diagrams - Team Kanban Project

Thư mục này chứa tất cả các sơ đồ PlantUML cho dự án Team Kanban.

## 📁 Cấu trúc

```
docs/plantuml/
├── erd.puml                          # ERD - Database Schema
├── use-case-diagram.puml             # Use Case Diagram tổng quan
└── use-cases/
    ├── uc1-authentication.puml       # UC1: Đăng ký và Đăng nhập
    ├── uc2-project-management.puml   # UC2: Quản lý Project
    ├── uc3-member-management.puml    # UC3: Quản lý Members
    ├── uc4-task-management.puml      # UC4: Quản lý Task
    ├── uc5-assign-claim-tasks.puml   # UC5: Gán và Nhận Task
    └── uc6-search-filter.puml        # UC6: Tìm kiếm và Lọc
```

## 🎨 Cách xem sơ đồ

### Cách 1: Sử dụng PlantUML Online Server
1. Truy cập: https://www.plantuml.com/plantuml/uml/
2. Copy nội dung file `.puml` và paste vào editor
3. Click "Submit" để xem sơ đồ

### Cách 2: Sử dụng VS Code Extension
1. Cài extension: **PlantUML** (jebbs.plantuml)
2. Mở file `.puml` trong VS Code
3. Nhấn `Alt + D` để xem preview
4. Hoặc click chuột phải → "Preview Current Diagram"

### Cách 3: Export ra hình ảnh
**Sử dụng PlantUML CLI:**
```bash
# Cài PlantUML
# brew install plantuml (macOS)
# apt-get install plantuml (Ubuntu)

# Export tất cả diagrams ra PNG
plantuml -tpng docs/plantuml/**/*.puml

# Export ra SVG (vector, chất lượng tốt hơn)
plantuml -tsvg docs/plantuml/**/*.puml
```

**Sử dụng VS Code Extension:**
- Chuột phải vào file `.puml` → "Export Current Diagram"
- Chọn format: PNG, SVG, PDF, etc.

## 📊 Danh sách sơ đồ

### 1. ERD - Entity Relationship Diagram
**File:** `erd.puml`

Sơ đồ cơ sở dữ liệu với 4 bảng:
- `users` - Người dùng
- `projects` - Dự án Kanban
- `project_members` - Thành viên của project
- `tasks` - Công việc

**Highlights:**
- Primary keys, Foreign keys
- Relationships (1-to-many, many-to-many)
- Constraints (UNIQUE, NOT NULL, CASCADE)
- Business rules notes

### 2. Use Case Diagram - Tổng quan
**File:** `use-case-diagram.puml`

Sơ đồ use case tổng thể với 6 use cases chính:
- UC1: Đăng ký và Đăng nhập
- UC2: Quản lý Projects
- UC3: Quản lý Members
- UC4: Quản lý Tasks
- UC5: Gán và Nhận Tasks
- UC6: Tìm kiếm và Lọc

### 3. UC1: Đăng ký và Đăng nhập
**File:** `use-cases/uc1-authentication.puml`

Activity diagram chi tiết cho:
- Flow đăng ký tài khoản mới (validation, hash password)
- Flow đăng nhập (authenticate, create session)
- Error handling (username exists, wrong password, etc.)

### 4. UC2: Quản lý Project
**File:** `use-cases/uc2-project-management.puml`

Activity diagram cho 4 thao tác:
- **Tạo project** - Tạo mới, auto add owner to members
- **Xem project** - Permission check, load board với 3 cột
- **Sửa project** - Owner only
- **Xóa project** - Owner only, CASCADE delete

### 5. UC3: Quản lý Members
**File:** `use-cases/uc3-member-management.puml`

Activity diagram cho:
- **Mời member** - Tìm user, check duplicates, add to project
- **Xem members** - Hiển thị danh sách với vai trò, ngày tham gia
- **Xóa member** - Set assigned_to = NULL for their tasks

**Authorization:** Owner only

### 6. UC4: Quản lý Task
**File:** `use-cases/uc4-task-management.puml`

Activity diagram cho:
- **Tạo task** - Owner only, gán assignee, upload file
- **Xem chi tiết task** - All members
- **Sửa task** - Owner hoặc assignee
- **Xóa task** - Owner only
- **Di chuyển task (drag & drop)** - Owner hoặc assignee

**Permission rules** rõ ràng cho từng thao tác.

### 7. UC5: Gán và Nhận Task
**File:** `use-cases/uc5-assign-claim-tasks.puml`

Activity diagram phân biệt 2 flows:

**Owner:**
- Gán task khi tạo mới
- Gán task đã tồn tại
- Hủy gán (set về unassigned)

**Member:**
- Tự nhận (claim) unassigned tasks
- Validation: task chưa được gán, không phải task của người khác

### 8. UC6: Tìm kiếm và Lọc
**File:** `use-cases/uc6-search-filter.puml`

Activity diagram cho:
- **Tìm kiếm** - Real-time AJAX, search trong title/description
- **Lọc** - Theo status, priority, assignee, due_date
- **Kết hợp tìm kiếm + lọc** - AND conditions
- **Phân trang** - Max 10 tasks/page

**Performance notes:** Indexes, JOIN optimization

## 🎯 Lưu ý khi sửa đổi

1. **Syntax PlantUML:**
   - ERD: Sử dụng `entity`, `primary_key`, `foreign_key`
   - Activity diagram: Sử dụng `|Swimlane|`, `if/else`, `switch/case`
   - Use case diagram: Sử dụng `usecase`, `actor`, relationships

2. **Consistency:**
   - Giữ màu sắc nhất quán (green=create, blue=view, yellow=update, pink=delete)
   - Sử dụng notes để giải thích business rules
   - Permission checks rõ ràng

3. **Testing:**
   - Test render trước khi commit
   - Đảm bảo không có syntax errors
   - Export ra PNG/SVG để kiểm tra visual

## 📚 Tài liệu tham khảo

- [PlantUML Official Documentation](https://plantuml.com/)
- [PlantUML Entity Relationship Diagram](https://plantuml.com/ie-diagram)
- [PlantUML Activity Diagram](https://plantuml.com/activity-diagram-beta)
- [PlantUML Use Case Diagram](https://plantuml.com/use-case-diagram)

## 🔄 Export cho báo cáo

Để export tất cả diagrams cho báo cáo:

```bash
# Export to PNG (cho Word/PowerPoint)
plantuml -tpng docs/plantuml/*.puml
plantuml -tpng docs/plantuml/use-cases/*.puml

# Export to SVG (cho LaTeX/web)
plantuml -tsvg docs/plantuml/*.puml
plantuml -tsvg docs/plantuml/use-cases/*.puml
```

Hình ảnh sẽ được lưu cùng thư mục với file `.puml`.

---

**Ngày tạo:** 20/03/2026
**Người tạo:** Nhóm CT214H - Kanban Project
