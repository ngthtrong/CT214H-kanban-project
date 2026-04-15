# Opening Script (Sections 1-3) - Bilingual

Scope of this script:

- Section 1: Introduction & Objectives
- Section 2: System Architecture
- Section 3: Database Design (detailed)

Speaker role:

- Opening presenter
- Handover to next presenter at end of Section 3

Format:

- VI: Vietnamese line to speak
- EN: English equivalent line

---

## 0) Opening Greeting (20-30s)

VI:
Kính chào thầy và các bạn. Hôm nay nhóm chúng em xin phép báo cáo project cho môn Web Programming. Đề tài nhóm thực hiện đề tài Kanban Project Model Management.

EN:
Good morning lecturer and everyone. We are Team Kanban, and our topic is Kanban Project Model Management for CT214H Web Programming. I will present the opening part, covering Sections 1 to 3: introduction, system architecture, and database design.

---

## 1) Section 1 - Introduction & Objectives (1.5-2 minutes)

### 1.1 Kanban Project Model Overview

VI:
Đề tài của nhóm em xây dựng một website quản lý công việc theo mô hình Kanban. Đây là luồng công việc được trực quan qua 3 trạng thái chính: To Do, In Progress và Done. Cách tổ chức này giúp nhóm theo dõi tiến độ rõ ràng, dễ nhìn thấy bottleneck và tối ưu quy trình thực hiện.

EN:
Our project builds a web-based task management system following the Kanban model. The workflow is visualized through three core states: To Do, In Progress, and Done. This structure helps teams track progress clearly, detect bottlenecks, and optimize execution flow.

### 1.2 Project Objectives

VI:
Về mục tiêu, nhóm em tập trung vào 3 điểm chính:
Thứ nhất, đảm bảo xác thực người dùng an toàn và quản lý hồ sơ cá nhân.
Thứ hai, hỗ trợ tạo workspace theo vai trò, tức là cộng tác có phân quyền.
Thứ ba, triển khai đầy đủ vòng đời task trên Kanban board.

EN:
For objectives, we focus on three main goals:
First, secure authentication and profile management.
Second, role-aware workspace collaboration.
Third, full task lifecycle implementation on the Kanban board.

### 1.3 Target Users and Roles

VI:
Trong mỗi project có 2 vai trò chính.
Owner có toàn quyền với thiết lập dự án và quản lý thành viên.
Member tập trung theo dõi tiến độ và cập nhật các task được giao.
Việc tách rõ vai trò giúp hệ thống an toàn hơn và hạn chế thao tác ngoài phạm vi cho phép.

EN:
Each project has two main roles.
The Owner has full authority over project settings and team management.
The Member focuses on progress tracking and updating assigned tasks.
This clear role separation improves security and prevents out-of-scope operations.

### Transition to Section 2

VI:
Sau phần giới thiệu, em chuyển sang kiến trúc hệ thống để thấy cách nhóm em tổ chức code và phân tách trách nhiệm giữa các lớp.

EN:
After this introduction, I will move to system architecture to show how we organized the codebase and separated responsibilities across layers.

---

## 2) Section 2 - System Architecture (1-1.5 minutes)

### 2.1 Architecture Style

VI:
Hệ thống của nhóm em đi theo hướng PHP-based Modular Monolith. Nghĩa là triển khai trong một codebase thống nhất, nhưng chia thành các module rõ ràng để dễ bảo trì và mở rộng.

EN:
Our system follows a PHP-based Modular Monolith architecture. It runs as one unified codebase, but with clearly separated modules for maintainability and extensibility.

VI:
Lý do chọn mô hình này là để cân bằng giữa tính đơn giản khi triển khai đồ án và tính chặt chẽ trong phân tách trách nhiệm.

EN:
We chose this model to balance implementation simplicity for coursework with strict separation of responsibilities.

### 2.2 Main Structure

VI:
Trong cấu trúc chính:

- Thư mục api chứa các JSON endpoints.
- Thư mục includes chứa business logic và kết nối cơ sở dữ liệu.
- Thư mục js xử lý tương tác AJAX và hành vi giao diện.
- Các file ở thư mục gốc là các trang giao diện người dùng.

EN:
In the main structure:

- The api folder contains JSON endpoints.
- The includes folder contains business logic and database connection modules.
- The js folder handles AJAX interactions and UI behavior.
- Root-level files are user-facing pages.

VI:
Nhờ cách chia này, frontend, API và domain logic không bị trộn lẫn, nên khi debug hoặc mở rộng tính năng sẽ nhanh hơn.

EN:
With this separation, frontend, API, and domain logic are not mixed together, so debugging and feature extension become faster.

### Transition to Section 3

VI:
Tiếp theo là phần quan trọng nhất trong mở đầu của em: thiết kế cơ sở dữ liệu, vì đây là nền tảng đảm bảo đúng nghiệp vụ và an toàn dữ liệu.

EN:
Next is the most important part of my opening: database design, because it is the foundation for business correctness and data safety.

---

## 3) Section 3 - Database Design (Detailed, 3-4 minutes)

### 3.1 Design Goal

VI:
Mục tiêu thiết kế database của nhóm em là đảm bảo 3 yếu tố: đúng nghiệp vụ cộng tác, toàn vẹn dữ liệu, và truy vấn hiệu quả khi số lượng task tăng.

EN:
Our database design aims for three factors: collaboration-oriented business correctness, strong data integrity, and efficient querying as task volume grows.

### 3.2 Entity Overview (Explain each table)

VI:
Hệ thống gồm 5 thực thể cốt lõi:

- Users: lưu thông tin tài khoản và hồ sơ.
- Projects: lưu không gian làm việc, owner và mã project.
- Project Members: bảng nối thể hiện user nào thuộc project nào, kèm role.
- Join Requests: lưu luồng xin tham gia và trạng thái duyệt.
- Tasks: lưu các đơn vị công việc với status, priority, due date và assignee.

EN:
The system has five core entities:

- Users: stores account and profile information.
- Projects: stores workspace data, owner, and project code.
- Project Members: junction table mapping users to projects with roles.
- Join Requests: stores join workflow and approval states.
- Tasks: stores work items with status, priority, due date, and assignee.

VI:
Điểm đáng chú ý là Project Members giúp chuẩn hóa quan hệ nhiều-nhiều giữa users và projects, đồng thời lưu thêm ngữ nghĩa vai trò.

EN:
A key point is that Project Members normalizes the many-to-many relation between users and projects while preserving role semantics.

### 3.3 Relationship Summary

VI:
Về quan hệ dữ liệu:

- Một user có thể sở hữu nhiều project.
- Một project có nhiều member và một member có thể tham gia nhiều project.
- Một project có nhiều join request.
- Một project có nhiều task.
- Một user có thể được assign nhiều task, và task cũng có thể để unassigned.

EN:
For data relationships:

- One user can own multiple projects.
- One project has many members, and one member can join multiple projects.
- One project can have many join requests.
- One project can have many tasks.
- One user can be assigned many tasks, and tasks can also stay unassigned.

### 3.4 Integrity and Constraint Semantics

VI:
Phần này rất quan trọng để bảo vệ dữ liệu:

- Mỗi bảng dùng primary key tự tăng để định danh duy nhất.
- Các trường nghiệp vụ quan trọng có unique constraint, ví dụ username, email, project_code.
- Bảng project_members có unique(project_id, user_id) để chặn trùng thành viên.
- Bảng join_requests có unique(project_id, user_id, status) để tránh yêu cầu trùng trạng thái.

EN:
This part is crucial for data protection:

- Every table uses an auto-increment primary key for unique identity.
- Important business fields have unique constraints, such as username, email, and project_code.
- Project_members uses unique(project_id, user_id) to prevent duplicate membership.
- Join_requests uses unique(project_id, user_id, status) to avoid duplicate requests in the same state.

VI:
Về khóa ngoại:

- Các quan hệ phụ thuộc project dùng ON DELETE CASCADE để không tạo dữ liệu mồ côi.
- Riêng tasks.assigned_to dùng ON DELETE SET NULL để giữ lại task khi user bị xóa khỏi vai trò assignee.

EN:
For foreign keys:

- Project-dependent relations use ON DELETE CASCADE to avoid orphan records.
- tasks.assigned_to uses ON DELETE SET NULL so tasks are preserved even when an assignee is removed.

### 3.5 Domain Constraints and Lifecycle

VI:
Để đảm bảo dữ liệu luôn hợp lệ, các trường trạng thái dùng ENUM:

- role: owner/member
- join request status: pending/approved/rejected
- task status: todo/in_progress/done
- priority: low/medium/high

EN:
To enforce valid domain values, status-like fields use ENUM:

- role: owner/member
- join request status: pending/approved/rejected
- task status: todo/in_progress/done
- priority: low/medium/high

VI:
Ngoài ra, hệ thống có lifecycle archive cho project và task bằng is_archived và archived_at. Cách này giúp ẩn dữ liệu khỏi luồng làm việc chính mà vẫn bảo toàn lịch sử để truy vết hoặc phục hồi.

EN:
In addition, the system supports archive lifecycle for projects and tasks using is_archived and archived_at. This hides data from active workflows while preserving history for traceability and restoration.

### 3.6 Why This Database Design Is Practical

VI:
Tóm lại, thiết kế database này giải quyết đồng thời ba bài toán:

- Đúng nghiệp vụ cộng tác theo vai trò.
- Chặt chẽ về toàn vẹn dữ liệu.
- Thuận lợi cho các tính năng phía trên như search/filter/pagination ở các phần sau của bài trình bày.

EN:
In summary, this database design solves three problems at once:

- Correct role-based collaboration behavior.
- Strong data integrity guarantees.
- Good support for upper-layer features such as search/filter/pagination, which will be presented in the next part.

---

## 4) Handover To Next Presenter (10-15s)

VI:
Trên đây là phần mở đầu từ mục 1 đến mục 3 của em. Tiếp theo, nhóm em xin chuyển sang mục 4 - Key Features để đi sâu vào luồng chức năng và triển khai thực tế trong code.

EN:
That concludes my opening presentation for Sections 1 to 3. Next, we will move to Section 4 - Key Features to dive deeper into functional flows and practical implementation in code.

---

## 1) Opening For My Part (20-30s)

VI:
Xin chào thầy và các bạn. Trong phần này, em chỉ tập trung vào module em phụ trách: Search, Filter, Sort và Pagination cho task. Các phần khác của hệ thống đã được nhóm em tóm tắt trước đó, nên ở đây em sẽ đi thẳng vào giá trị thực tế và luồng chạy trong code.

EN:
Hello everyone. In this part, I will focus only on my own module: Search, Filter, Sort, and Pagination. Other system modules were already introduced earlier, so here I will go directly to practical value and real code flow.

---

## 2) Slide Talk - Key Feature Summary (1.5-2 minutes)

### 2.1 Why This Feature Matters

VI:
Khi số lượng task tăng, người dùng không thể thao tác hiệu quả nếu chỉ kéo thả thủ công trên board. Vì vậy, em xây dựng Search/Filter/Sort/Pagination để rút ngắn thời gian tìm task, giảm tải truy vấn, và giữ trải nghiệm ổn định.

EN:
As the number of tasks grows, users cannot work efficiently if they rely only on manual board scanning. So I built Search/Filter/Sort/Pagination to reduce lookup time, reduce query load, and keep the UX stable.

### 2.2 Search + Filter

VI:
Search cho phép tìm theo từ khóa ở tiêu đề và mô tả task, và ở luồng global còn tìm được theo tên project. Filter hỗ trợ nhiều điều kiện như trạng thái, độ ưu tiên, người thực hiện, task quá hạn, task trong tuần. Các điều kiện được kết hợp linh hoạt để tạo thành pipeline truy vấn động.

EN:
Search supports keyword matching on task title and description, and in the global flow it also matches project name. Filter supports multiple criteria such as status, priority, assignee, overdue tasks, and tasks due this week. These criteria are combined into a dynamic query pipeline.

### 2.3 Sort + Pagination

VI:
Sort giúp người dùng ưu tiên đúng việc cần làm: theo priority, due date, ngày tạo, hoặc tiêu đề. Pagination chia nhỏ dữ liệu theo trang để không tải toàn bộ dữ liệu một lần, giúp hệ thống phản hồi nhanh hơn khi dữ liệu lớn.

EN:
Sort helps users prioritize the right work: by priority, due date, creation date, or title. Pagination splits data into pages instead of loading everything at once, so the system stays responsive with larger datasets.

### 2.4 Security + Access Control

VI:
Một điểm quan trọng là mọi truy vấn đều đi kèm kiểm tra quyền. Người dùng chỉ thấy task trong project mà họ là thành viên. Điều này ngăn rò rỉ dữ liệu nội bộ giữa các project.

EN:
A critical point is that every query is guarded by access control. Users can only see tasks in projects where they are members. This prevents internal data leakage across projects.

---

## 3) Transition From Slide To Code Demo (10-15s)

VI:
Sau đây em sẽ show code và đi theo đúng luồng runtime: từ UI nhập filter, đến API nhận tham số, rồi backend dựng SQL động, cuối cùng trả kết quả và metadata phân trang về frontend.

EN:
Now I will show the code and follow the exact runtime flow: from UI filter input, to API parameter handling, then dynamic SQL building in backend, and finally returning results with pagination metadata to frontend.

---

## 4) Live Code Walkthrough Script (4-6 minutes)

## Step A - Global Search UI

Code location for demo:

- search.php, lines around 31-120

VI:
Ở trang search global, em có form với các trường search text, project, assignee, status, priority, sort by, sort direction và các quick filter. Mục tiêu là gom toàn bộ điều kiện truy vấn vào một giao diện duy nhất trước khi gửi request.

EN:
In the global search page, I provide one form with search text, project, assignee, status, priority, sort by, sort direction, and quick filters. The goal is to collect all query conditions in one place before sending the request.

## Step B - Frontend Builds Query Params

Code location for demo:

- js/search-global.js, initGlobalSearchHub
- js/search-global.js, collectFilters
- js/search-global.js, performSearch

VI:
Frontend dùng state để giữ bộ lọc hiện tại. Khi người dùng bấm tìm kiếm, hàm collectFilters gom dữ liệu từ form, sau đó performSearch dùng URLSearchParams để đóng gói query string như search, status, priority, assigned_to, sort_by, sort_dir, page và per_page.

EN:
Frontend uses a state object to store current filters. When users trigger search, collectFilters gathers form values, then performSearch uses URLSearchParams to build query string fields such as search, status, priority, assigned_to, sort_by, sort_dir, page, and per_page.

VI:
Quick filter như mine, overdue, this_week được map thành cờ logic trước khi gửi API, nên UI rất nhanh mà vẫn nhất quán với backend.

EN:
Quick filters like mine, overdue, and this_week are mapped to logic flags before calling the API, so the UI is fast while staying consistent with backend logic.

## Step C - API Receives and Routes Search Request

Code location for demo:

- api/tasks.php, handleGet

VI:
Trong API tasks, nếu có global=1 thì hệ thống đi vào nhánh global search. Tại đây backend chuẩn hóa các tham số filter và gọi searchUserTasks. Nếu có filters=1 thì trả về danh sách option để frontend dựng dropdown.

EN:
In the tasks API, when global=1 is present, the request enters the global search branch. Backend normalizes filter parameters and calls searchUserTasks. If filters=1 is present, it returns filter options for frontend dropdowns.

## Step D - Dynamic SQL + Access Boundary

Code location for demo:

- includes/task-functions.php, searchUserTasks

VI:
Đây là phần cốt lõi của pipeline. Backend khởi tạo mảng conditions và params. Mỗi filter hợp lệ sẽ thêm một điều kiện vào WHERE. Đồng thời luôn có điều kiện membership pm.user_id = current user để đảm bảo chỉ truy xuất task thuộc project người dùng được phép xem.

EN:
This is the core pipeline. Backend initializes conditions and params arrays. Every valid filter appends one WHERE condition. At the same time, membership condition pm.user_id = current user is always included, ensuring users can only query authorized project tasks.

VI:
Các giá trị filter đều đi qua prepared parameters, giúp tránh SQL injection và giữ query rõ ràng khi mở rộng thêm điều kiện.

EN:
Filter values are bound through prepared parameters, which helps prevent SQL injection and keeps query composition clean when adding new conditions.

## Step E - Sort Strategy

Code location for demo:

- includes/task-functions.php, _taskBuildSearchOrderClause

VI:
Sort trong global search được điều khiển bằng whitelist switch-case. Tức là chỉ một tập sort_by hợp lệ mới được map sang ORDER BY. Cách này an toàn hơn so với nối trực tiếp input người dùng vào SQL.

EN:
Sorting in global search is controlled by a switch-case whitelist. Only valid sort_by values are mapped to ORDER BY. This is safer than directly concatenating user input into SQL.

VI:
Riêng priority được map bằng CASE expression để đảm bảo thứ tự low, medium, high đúng nghiệp vụ.

EN:
For priority, a CASE expression is used to enforce business order low, medium, high.

## Step F - Pagination Math + Metadata

Code location for demo:

- includes/task-functions.php, searchUserTasks

VI:
Hệ thống tính total bằng COUNT query, rồi tính page, per_page, offset và total_pages. Sau đó SELECT dữ liệu theo LIMIT/OFFSET. Kết quả trả về gồm tasks, grouped_by_project, summary và pagination metadata như has_prev, has_next để frontend render phân trang.

EN:
The system computes total with a COUNT query, then derives page, per_page, offset, and total_pages. It fetches data using LIMIT/OFFSET. The response contains tasks, grouped_by_project, summary, and pagination metadata like has_prev and has_next so frontend can render pagination.

## Step G - Frontend Renders Result + Prev/Next

Code location for demo:

- js/search-global.js, renderSummary
- js/search-global.js, renderResults
- js/search-global.js, renderPagination

VI:
Frontend hiển thị tổng số task, số project liên quan, danh sách task theo nhóm project và nút Trước/Sau. Khi đổi trang, hàm performSearch được gọi lại với page mới.

EN:
Frontend shows total tasks, number of related projects, grouped task list, and Prev/Next buttons. When page changes, performSearch is called again with the new page.

---

## 5) Project Board Flow (Important Clarification) (1-1.5 minutes)

Code location for demo:

- project.php, filter controls
- js/search-filter.js, collectFiltersFromDom
- js/kanban.js, loadTasks
- js/kanban.js, sortTasksByFilters
- js/kanban.js, renderColumnPagination
- includes/task-functions.php, searchProjectTasks

VI:
Ngoài global search, project board cũng có filter và sort. Luồng ở đây khác một chút:

1) Filter cơ bản (search, status, priority, assigned_to) gửi lên server để lọc dữ liệu.
2) Sort trên board được xử lý phía frontend bằng sortTasksByFilters.
3) Pagination trên board là pagination theo từng cột (To Do, In Progress, Done) ở frontend.

EN:
Besides global search, the project board also supports filtering and sorting. This flow is slightly different:

1) Basic filters (search, status, priority, assigned_to) are sent to server.
2) Board sorting is handled on frontend via sortTasksByFilters.
3) Board pagination is column-level pagination (To Do, In Progress, Done) on frontend.

VI:
Điểm này cho thấy hệ thống đang dùng hybrid strategy: server-side filter để giảm dữ liệu không cần thiết, và client-side sort/pagination để tương tác board mượt hơn.

EN:
This shows a hybrid strategy: server-side filtering to cut irrelevant data, and client-side sort/pagination for smoother board interaction.

---

## 6) Closing For My Part (20-30s)

VI:
Tóm lại, phần em phụ trách tập trung vào khả năng truy xuất task nhanh, chính xác và an toàn khi dữ liệu tăng. Em triển khai pipeline rõ ràng từ UI đến API và SQL động, đồng thời giữ quyền truy cập chặt chẽ theo membership.

EN:
In summary, my part focuses on fast, accurate, and secure task retrieval at larger scale. I implemented a clear pipeline from UI to API to dynamic SQL, while keeping strict membership-based access control.

---

## 7) Fast Q&A Backup Lines

Q1: Why use dynamic SQL conditions?

VI:
Vì số lượng filter là tùy chọn. Dynamic conditions giúp query linh hoạt nhưng vẫn dùng prepared params để an toàn.

EN:
Because filters are optional. Dynamic conditions keep queries flexible while still using prepared params for safety.

Q2: Why not always paginate on server for project board?

VI:
Board cần cảm giác thao tác tức thời theo cột, nên em dùng hybrid approach. Filter ở server, còn sort và phân trang theo cột ở client để UX mượt hơn.

EN:
The board needs instant column interaction, so I use a hybrid approach. Filtering is server-side, while sorting and column pagination are client-side for smoother UX.

Q3: How is data leakage prevented?

VI:
Mọi query đều gắn điều kiện membership hoặc check quyền truy cập project trước khi truy vấn task.

EN:
Every query is guarded by membership conditions or explicit project access checks before retrieving tasks.
