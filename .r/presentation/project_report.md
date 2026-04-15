# Project Report (Markdown Export)

- Nguon: docs/Project_Report.pdf
- Phuong phap: tu dong bang pdftotext + pdftoppm (uu tien day du noi dung).

## Noi dung text trich xuat (da format)

### Cover Information

**Can Tho University**  
College of Information and Communication Technology

**PROJECT REPORT**  
Web Programming - CT214H

**Topic:** Kanban Project Model Management

**Instructor:** PhD. Triệu Thanh Ngoan

| Student Name | ID |
| --- | --- |
| Nguyễn Thanh Trọng | B2305615 |
| Ngô Hưng Thịnh | B2303904 |
| Cao Tường Hưng | B2303873 |
| Huỳnh Hồng Ân | B2306657 |

Can Tho, 04/2026

## Index

1. Introduction
1.1. Kanban project model
1.2. Target users and roles
1.3. Objectives
1.4. Team assignment and responsibilities
2. System architecture
2.1. Usecase diagram
2.2. Folder structure
3. Database design
3.1. Entity overview
3.2. Relationship summary
3.3. Constraint semantics
3.3.1. Entity integrity
3.3.2. Uniqueness semantics
3.3.3. Referential integrity and delete behavior
3.3.4. Domain constraints
3.3.5. Temporal and lifecycle constraints
4. Key features
4.1. Authentication
4.2. User profile
4.3. Search/filter and pagination
4.3.1. Search/filter
4.3.2. Pagination
4.4. Project management
4.4.1. Project CRUD and archive lifecycle
4.4.2. Project code generation and lookup
4.4.3. Join request review
4.4.4. Owner/member access boundaries
4.5. Task management
4.5.1. Task CRUD and archive lifecycle
4.5.2. Drag-and-drop status update
4.5.3. Assignment
4.5.4. Attachment handling
5. Challenges faced and solutions
5.1. Search/filter and pagination pipeline
5.2. Task management
5.3. Project management
5.4. Authentication
5.5. Security
6. Conclusion and future improvements
6.1. Lessons learned
6.2. Limitations
6.3. Future improvements

## Table Index

- Table 1. Team responsibilities
- Table 2. Entity overview
- Table 3. Database relationship
- Table 4. Project permission
- Table 5. Task permission

## 1. INTRODUCTION

This project focuses on creating a collaborative project management website based on the Kanban model. The website allows users to create their own projects, manage team collaboration by accepting participation requests through unique project code and assigning roles, and track overall progress. Additionally, it provides tools to create tasks and manage specific files attached to each individual task.

### 1.1. Kanban project model

- The Kanban framework originated in the late 1940s when Toyota developed it to optimize their manufacturing and engineering processes.
- Kanban works by visualizing tasks on a board divided into columns, where each column represents a specific stage of the workflow. Each task is represented by a card that moves from left to right across the board as work progresses.
- Kanban boards can feature numerous variations of task states depending on a team's specific needs. For this project, we focus on the most popular and universally used configuration: the trio of "To Do", "In Progress", and "Done".

### 1.2. Target users and roles

Within each project, users are categorized into two main roles with distinct permissions:

- **Project Member:** Can view the entire project board to understand the overall progress. They are responsible for tasks directly assigned to them. For their specific tasks, members can update task progress and manage task-related resources.
- **Project Owner:** Inherits all capabilities of a Project Member, with the added authority to manage project settings, oversee all tasks, and manage the team (including inviting or removing members and assigning specific roles).

### 1.3. Objectives

- Deliver secure user registration, login, logout, and profile management.
- Provide project workspace creation with role-aware collaboration.
- Implement full task lifecycle management in a three-column Kanban board.
- Support task search/filter and paginated retrieval for scalability.
- Enforce safe file upload for avatars and task attachments.

### 1.4. Team assignment and responsibilities

| Member | Main Responsibilities |
| --- | --- |
| Nguyễn Thanh Trọng (Leader) | Module 1 owner: database and search/filter pipeline (schema design, sample data, multi-criteria filtering, pagination, query-oriented constraints), dark-mode. |
| Huỳnh Hồng Ân | Module 2 owner: task management (task CRUD, drag-and-drop status updates, assignment/claim logic, attachment operations). |
| Ngô Hưng Thịnh | Module 3 owner: project and member management (project CRUD, project code, join request review, owner/member access boundaries, security aspects). |
| Cao Tường Hưng | Module 4 owner: user authentication and base UI (registration, login/logout, profile, avatar workflow, session-related integration). Coordinates cross-module integration and report consolidation. |

*Table 1. Team responsibilities*

## 2. SYSTEM ARCHITECTURE

### 2.1. Usecase diagram

(Usecase diagram is referenced in the original PDF.)

### 2.2. Folder structure

```text
|-- api/               # api files
|-- css/               # css files
|-- database/          # migration files
|-- includes/          # helper/business functions
|-- js/                # javascript files
|-- uploads/           # uploaded assets
|-- index.php
|-- join-project.php
|-- join-requests.php
|-- login.php
|-- logout.php
|-- members.php
|-- profile.php
|-- project.php
|-- register.php
|-- search.php
`-- task.php
```

## 3. DATABASE DESIGN

### 3.1. Entity Overview

| Entity | Business role | Key attributes |
| --- | --- | --- |
| users | Stores account information of system users | username, email, full_name, avatar |
| projects | Stores Kanban project information and ownership | owner_id, project_name, project_code, is_archived |
| project_members | Junction table for users participating in projects | project_id, user_id, role, joined_at |
| project_join_requests | Stores project join requests and their processing status | project_id, user_id, status, requested_at, responded_at |
| tasks | Stores work items in each project | project_id, assigned_to, column_status, priority, due_date, is_archived |

*Table 2. Entity overview*

### 3.2. Relationship Summary

| Relationship | Cardinality | Meaning |
| --- | --- | --- |
| users -> projects | 1-N | One user can own multiple projects |
| users <-> projects (via project_members) | N-N | A user can join many projects, and a project can have many members |
| projects -> project_join_requests | 1-N | A project can have many join requests |
| users -> project_join_requests | 1-N | A user can submit many requests across projects |
| projects -> tasks | 1-N | A project contains many tasks |
| users -> tasks | 1-N (optional) | A user can be assigned many tasks, and a task can remain unassigned |

*Table 3. Database relationship*

Design notes:

- The N-N relationship between users and projects is normalized through the project_members junction table to capture role and joined_at.
- `tasks.assigned_to` is nullable to support the task-claim workflow.

### 3.3. Constraint Semantics

#### 3.3.1. Entity Integrity

All tables use non-null `AUTO_INCREMENT` primary keys, ensuring each record is uniquely identified and preserving row-level entity integrity.

#### 3.3.2. Uniqueness Semantics

Uniqueness is enforced on key business fields: `users.username` and `users.email` are globally unique, `projects.project_code` is unique for join-by-code access, `project_members` uses `UNIQUE(project_id, user_id)` to prevent duplicate membership, and `project_join_requests` uses `UNIQUE(project_id, user_id, status)` to avoid duplicate requests with the same status.

#### 3.3.3. Referential Integrity and delete behavior

Foreign-key behavior is designed to prevent orphan data: project- and user-linked relations in `projects`, `project_members`, `project_join_requests`, and `tasks.project_id` use `ON DELETE CASCADE` so dependent rows are cleaned automatically. In contrast, `tasks.assigned_to` uses `ON DELETE SET NULL`, allowing a task to remain when its assignee is removed.

#### 3.3.4. Domain Constraints

State and classification fields are constrained with `ENUM`:

- `project_members.role` (`owner`, `member`)
- `project_join_requests.status` (`pending`, `approved`, `rejected`)
- `tasks.column_status` (`todo`, `in_progress`, `done`)
- `tasks.priority` (`low`, `medium`, `high`)

This prevents invalid values from entering the database layer.

#### 3.3.5. Temporal and Lifecycle Constraints

Timestamp fields are automated through `CURRENT_TIMESTAMP`: `created_at` captures creation time and `updated_at` tracks the latest modification. In addition, projects and tasks use `is_archived` with `archived_at` for soft-archive lifecycle management, preserving historical data for traceability and possible restoration.

## 4. Key Features

### 4.1. Authentication

- **Register:** Once the user fills out the registration form, the system triggers the `registerUser` function. This function validates input data (length checks and email format) and queries the database to ensure the username or email is not already in use. If valid, the password is hashed before saving to the `users` table. If validation fails, an error message is shown; otherwise, the user is redirected to the login page.
- **Login:** After the user submits credentials, the system calls `authenticateUser`. This function retrieves the user's record from the database using username or email. If the user is not found or password verification fails, an error message is displayed. On success, the system calls `loginUser` to establish session variables so the user does not need to log in again during subsequent visits.
- **Logout:** When the user logs out, the system triggers `logoutUser`, clears all user data from the current session, destroys the session on the server, and redirects the user to the login page.

### 4.2. User Profile

Includes: change avatar, add avatar, delete avatar, and update personal information.

When a user updates profile information or changes password, the system triggers `updateUserProfile` or `changeUserPassword` to validate input and securely update the database using prepared statements.

For profile picture handling, `uploadUserAvatar` verifies the file format (e.g., JPG, PNG), generates a random filename to prevent overwrite collisions, and stores the file in `uploads/avatars/`. If the user removes their avatar, the system clears the filename in the database and deletes the physical file from the server.

### 4.3. Search/Filter and Pagination

#### 4.3.1. Search/Filter

The system provides a multi-criteria Search/Filter Pipeline that lets users query tasks by combined conditions such as keywords, status, priority, and assignees. Backend services dynamically construct SQL queries using prepared statements for accurate results. The pipeline also enforces strict access control so users can only query data in projects they are authorized to access, preventing internal data leakage.

#### 4.3.2. Pagination

To optimize performance and user experience, the system uses pagination when listing projects and tasks. Instead of loading all data at once, it fetches a fixed number of records per page (`ITEMS_PER_PAGE`) and dynamically computes `OFFSET` and `LIMIT` based on the requested page. This reduces database load and keeps the UI responsive and easy to navigate as data volume grows.

### 4.4. Project Management

#### 4.4.1. Project CRUD and Archive Lifecycle

The system provides project lifecycle management including CRUD, archive, and unarchive operations. Mutations are owner-controlled: only users with Project Owner role can execute actions that alter project state or structure (e.g., modify, archive, delete). Backend permission checks prevent unauthorized changes from regular members or outsiders.

#### 4.4.2. Project Code Generation and Lookup

To create a unique project code, the system generates an 8-character string from uppercase letters and digits. It uses `random_int()` for cryptographic randomness and performs a collision check loop against the database until a unique code is found.

To find a project, users enter the code. Before submitting a join request, the system verifies project existence and membership status. If valid, users can send a request for owner review. This approach keeps projects private while enabling easy teammate onboarding.

#### 4.4.3. Join Request Review

Project owners control team membership through a Join Request Review process. Requests created by code-based join attempts are stored with `pending` status. Owners can accept or reject each request. Approved requests automatically insert rows into `project_members` with `member` role, granting access to the Kanban board.

#### 4.4.4. Owner/Member Access Boundaries

The system enforces strict Owner/Member boundaries so sensitive administrative operations are owner-only.

Backend functions (`projectIsOwner`, `taskIsOwner`) validate permissions before critical mutations. Members can view the board and update authorized tasks, while owners can edit project settings, review join requests, and delete projects. Crafted API calls are rejected server-side with `403 Forbidden` when unauthorized.

| Rule Description | Condition | Allowed Actor | Expected Behavior |
| --- | --- | --- | --- |
| Create project | Valid project payload | Authenticated user | Project is created; caller becomes owner-member |
| Update/delete/archive/unarchive project | Caller is project owner | Owner | Project state mutation is applied |
| List project detail | Caller is project member | Owner, Member | Project data is returned |
| Add member | Caller is project owner and target user exists | Owner | User becomes project member |
| Remove member | Caller is project owner and target is not owner | Owner | Member removed and assignments set `NULL` |
| Leave project | Caller is member and not owner | Member | Caller removed and assignments set `NULL` |
| Submit join request | Project active; caller not member; no pending duplicate | Authenticated user | Pending request is created |
| Approve/reject join request | Caller is project owner; request status pending | Owner | Request state updated; approval inserts membership |
| Find project by code | Code exists and project active | Authenticated user | Project preview is returned |

*Table 4. Project permission*

### 4.5. Task Management

#### 4.5.1. Task CRUD and Archive Lifecycle

This lifecycle is protected by role-based policies so only authorized users (Project Owners or assigned members) can modify task data. Data integrity is preserved by cleaning physical attachments when a task or project is permanently deleted.

- **Create:** Owner can create tasks with details (title, description, priority, due date).
- **Read/Update:** Users read task details via modal interface and update attributes as the project evolves.
- **Archive/Unarchive:** Tasks can be hidden from active board while still retained in the database.
- **Hard delete:** Permanent removal path is supported for cleanup.

#### 4.5.2. Drag-and-Drop Status Update

The UI uses drag-and-drop with optimistic updates for smooth interaction. Each movement triggers an asynchronous API request to persist the change. If permission checks fail, the UI rolls the task back to its previous position and shows an error message, ensuring visual board state matches database truth.

#### 4.5.3. Assignment

Assignment is owner-controlled: only Project Owner can assign or change assignees. In task edit flow, owner selects from verified project members only, preventing unauthorized distribution and ensuring valid ownership.

#### 4.5.4. Attachment Handling

The attachment subsystem allows up to 5 files per task. Security controls include extension whitelisting and MIME validation to block malicious uploads. Files are renamed with random strings to avoid collisions and traversal risks. When tasks or projects are deleted, related files in `uploads/` are removed for integrity and storage hygiene.

| Rule Description | Condition | Allowed Actor | Expected Behavior |
| --- | --- | --- | --- |
| Create task in project scope | Caller is project member | Owner, Member | Task is created |
| Edit task content | Caller is owner or assigned member | Owner, Assigned Member | Task fields are updated |
| Delete task permanently | Caller is project owner | Owner | Task and linked file are removed |
| Move task by drag-drop | Caller is owner or assigned member | Owner, Assigned Member | Status is updated |
| Reassign task | Target assignee is project member | Owner | `assigned_to` is updated |
| Archive/unarchive task | Owner-only action | Owner | Task active state is toggled |
| Upload/replace attachment | Caller can edit task | Owner, Assignee Member | File is validated and linked |
| Read project tasks | Membership required | Owner, Member | Task list is returned |

*Table 5. Task permission*

## 5. Challenges Faced and Solutions

### 5.1. Search/Filter and Pagination Pipeline

#### 5.1.1. Search/Filter Pipeline

**Development challenge**

The main challenge is preserving query semantics when combining many filters while preventing data leakage outside project scope.

**Implemented solution**

- Build dynamic conditions with `$conditions` and `$params` arrays.
- Append conditions only when filter values are valid.
- Execute membership validation at function entry before query construction.
- Return `filters` in response to keep UI state synchronized.

**Edge cases and failure handling**

- Caller is not a project member: return access-denied error.
- Invalid filter values: ignore invalid domain conditions.
- No matched tasks: return empty list and render empty state in UI.

#### 5.1.2. Pagination Pipeline

**Development challenge**

The main risk is mismatch between total count and page data if count and data queries do not share identical filter conditions.

**Implemented solution**

- Reuse the same `whereClause` for both `COUNT(*)` and `SELECT`.
- Separate total-count and data-retrieval steps clearly.
- Return complete pagination metadata to avoid ambiguous frontend behavior.

**Edge cases and failure handling**

- `page` exceeds total pages: return empty list with valid metadata.
- Extremely large `per_page`: backend clamps to allowed range.
- Filters change across page transitions: response still echoes active filters.

### 5.2. Task Management

#### 5.2.1. Task CRUD and Archive Lifecycle

**Development challenge**

The critical challenge was managing two deletion semantics simultaneously:

- reversible hiding (soft delete via archive)
- irreversible removal (hard delete with file cleanup)

Without strict separation, the system risks inconsistent board state and orphan attachment artifacts.

**Implemented solution**

The domain layer provides dedicated action paths:

- archive/unarchive toggles state flags (`is_archived`, `archived_at`)
- hard delete removes task row and associated attachment file

The query model explicitly separates active vs archived retrieval, making board rendering deterministic and traceable.

**Edge cases and failure handling**

- Attempt to archive a non-existing task: rejected with explicit error.
- Attempt to unarchive an already active task: treated idempotently.
- Non-owner delete attempt: blocked by authorization gate.
- Missing or malformed update payload: rejected before persistence.

#### 5.2.2. Drag-and-Drop Status Update

**Development challenge**

The main engineering risk was UI-database divergence when API calls fail after optimistic render.

**Implemented solution**

The UI stores prior task status before mutation. If API returns failure, rollback is executed immediately and drag-drop state is reset. This guarantees eventual consistency between visual state and persisted state without sacrificing responsiveness.

**Edge cases and failure handling**

- Drop to same status column: no-op.
- Invalid status payload: backend rejects and UI reverts.
- Unauthorized move by non-assignee member: backend rejects and UI reverts.
- Temporary network failure: rollback plus error toast preserves user trust.

#### 5.2.3. Assignment and Claim Logic

**Development challenge**

The main challenge was preventing permission leakage between edit and assign operations, especially when payloads contain mixed fields.

**Implemented solution**

Assignment logic is isolated and conditionally executed only under owner privilege in update flow. Claim is implemented as a dedicated API action with explicit preconditions. This reduces ambiguity and improves auditability of allocation changes.

**Edge cases and failure handling**

- Owner assigns to removed/non-member user: rejected.
- Member attempts to reassign via crafted payload: ignored/rejected by backend.
- Concurrent claim attempts: first successful update wins, later attempts fail due to non-`NULL` assignee.

#### 5.2.4. Attachment Handling

**Development challenge**

The challenge was balancing usability and security while avoiding partial-state persistence (file stored but task reference not updated, or vice versa).

**Implemented solution**

The flow enforces validation before persistence and updates task metadata only after successful file write. Authorization checks are executed before mutation. Cleanup is integrated in hard delete to minimize storage residue.

**Edge cases and failure handling**

- Oversized file: rejected with explicit message.
- Unsupported file extension: rejected early.
- Permission failure on upload: no metadata mutation.
- Update failure after upload: controlled error path prevents silent inconsistency.

### 5.3. Project Management

#### 5.3.1. Project CRUD and Archive Lifecycle

**Development challenge**

The key challenge was supporting both reversible archive state and irreversible delete behavior while keeping cross-table consistency.

**Implemented solution**

- Introduced explicit archive/unarchive actions with state flags.
- Kept delete path owner-only and added pre-delete attachment cleanup.
- Relied on relational constraints for consistent cascade behavior across project-linked entities.

**Edge cases and failure handling**

- Non-owner attempts project mutation: blocked with role error.
- Archive already archived project: treated idempotently.
- Unarchive active project: treated idempotently.
- Missing project ID in mutation request: rejected at API validation layer.

#### 5.3.2. Project Code Generation and Lookup

**Development challenge**

Balancing easy sharing (simple short code) with collision safety and controlled information exposure.

**Implemented solution**

- Random 8-character code with collision retry loop.
- Lookup constrained to active projects.
- Returned preview payload is scoped and minimal for join decision.

**Edge cases and failure handling**

- Invalid/non-existing code: explicit not-found response.
- Archived project code: treated as unavailable for join flow.
- User skips lookup and attempts join without valid project context in UI: blocked client-side.

#### 5.3.3. Member Invitation and Membership Updates

**Development challenge**

Preventing role escalation and avoiding orphan assignment references when membership changes.

**Implemented solution**

- Centralized owner/member checks in member functions.
- Added assignment cleanup before membership deletion.
- Reused pending-request approval path to avoid duplicate onboarding logic.

**Edge cases and failure handling**

- Add existing member: rejected with clear message.
- Add unknown identifier: rejected.
- Remove non-member: rejected.
- Owner attempts self-leave through member flow: rejected.

#### 5.3.4. Join Request Review Flow

**Development challenge**

Avoiding race/duplication issues while preserving clear request state transitions.

**Implemented solution**

- Enforced request status check before approve/reject.
- Added duplicate-pending prevention in request creation.
- Applied owner-only gate on review endpoints.

**Edge cases and failure handling**

- Request already processed: reject re-processing.
- Non-owner tries approve/reject: blocked.
- User already member requests join: blocked.
- Request references missing project: blocked.

#### 5.3.5. Owner/Member Access Boundaries

**Development challenge**

Ensuring role policy is enforced consistently across multiple endpoints and business paths.

**Implemented solution**

- Embedded role checks at business-function entry points.
- API responses standardized to expose permission errors cleanly.
- Reused centralized membership/owner helper functions to reduce policy drift.

**Edge cases and failure handling**

- Direct API call bypassing UI: still blocked by backend checks.
- Owner/member role confusion across archived project scope: archived project checks prevent invalid actions.
- Missing IDs/parameters in role-sensitive actions: rejected early by API validation.

### 5.4. Authentication

**Maintain login state of user**

- Because HTTP is stateless, without sessions the server cannot identify whether a user moving from `index.php` to `profile.php` is the same person.
- The session stores information about the authenticated user.

### 5.5. Security

- Untrusted data in fields like "Họ và tên", "Tên dự án", "Tiêu đề task", and "Mô tả" may contain malicious input such as XSS or SQL commands. To prevent this, the system uses `htmlspecialchars()` to encode special characters (`<`, `>`, ...) and PDO prepared statements with placeholders (`?`) for SQL queries.
- Avatar and task attachment uploads are high-risk. The system validates both file extension and MIME type, renames files with random strings to prevent collisions, and uses `basename()` to reduce directory traversal risk.

## 6. Conclusion and Future Improvements

### 6.1. Lessons Learned

- The team gained practical understanding of integrating frontend interfaces with backend logic while maintaining a clear directory structure.
- The team learned to identify critical security risks from untrusted input and unsafe uploads, then researched and applied mitigation strategies.
- The team developed a proactive mindset to identify potential development difficulties early and solve them effectively.

### 6.2. Limitations

- The current system supports only a basic three-column Kanban model (To Do, In Progress, Done), without workflow customization.
- Real-time interactions (invitations, task status updates) still rely on reload/manual API polling because WebSockets are not yet implemented.
- While common vulnerabilities are addressed, advanced protections such as CSRF defense and rate limiting against brute-force attacks are not yet deployed.

### 6.3. Future Improvements

- Implement multi-tenant architecture to support independent organizations/teams with isolated data workspaces.
- Allow customizable Kanban columns and add a Backlog feature for upcoming tasks.
- Integrate WebSockets for live board updates and push notifications for key activities.
- Strengthen security with more advanced protective mechanisms.
