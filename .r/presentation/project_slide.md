---
marp: true
theme: default
paginate: true
---
# CT214H WEB PROGRAMMING

## Kanban Project Model Management

**Nguyễn Thanh Trọng** - B2305615
**Ngô Hưng Thịnh** - B2303904
**Cao Tường Hưng** - B2303873
**Huỳnh Hồng Ân** - B2306657

**Instructor:** PhD. Triệu Thanh Ngoan

---

# Table of Contents

1. Introduction & Objectives
2. System Architecture
3. Database Design
4. Key Features
5. Security & Conclusion

---

# 1. Introduction

## Kanban Project Model Overview:

- Visualize workflow through three stages:
  - To Do
  - In Progress
  - Done
- Supports clear progress tracking and engineering process optimization.

### Project Objectives

- Secure user authentication and profile management
- Role-aware workspace creation
- Full task lifecycle on Kanban boards

## Target Users & Roles

- **Owner**
  - Full authority over project settings and team management
  - Controls lifecycle actions and permissions
- **Member**
  - Tracks project progress
  - Updates assigned tasks

---

# 2. System Architecture

## PHP-based Modular Monolith

- Balances simplicity and strict separation of concerns
- Maintains clean boundaries between presentation and domain logic

### Main Structure

- `/api` - JSON endpoints
- `/includes` - Domain logic and database connection files
- `/js` - Optimistic UI and AJAX handlers
- `/` - User interface pages

---

# 3. Database Design

## Entity Overview

Core business entities in the Kanban lifecycle:

- **Users**: Account credentials and profile metadata
- **Projects**: Workspace ownership and unique access codes
- **Members**: Junction entity mapping users to projects with roles
- **Join Requests**: Invitation and approval flow management
- **Tasks**: Work units with status, priority, and assignees

---

# 4. Key Features

- Search/Filter & Pagination
- Authentication System
- Project Management
- Task Management

---

# 4. Key Features

## Search/Filter & Pagination

### Search/Filter Pipeline

- The system provides a multi-criteria Search/Filter Pipeline allowing users to query tasks based on keywords, status, priority, and assignees.
- Security: Integrates strict access control to ensure users only view data within authorized projects, preventing internal data leakage.
- Backend services dynamically construct SQL queries using Prepared Statements for both accuracy and protection against SQL injection.

### Pagination

* Use pagination for project and task lists.
* Show a fixed number of items per page (ITEMS_PER_PAGE).
* Compute OFFSET and LIMIT from the selected page.
* Reduce database load and keep the UI fast as data grows.

# 5. Conclusion

### Lessons Learned

- Stronger understanding of frontend-backend integration
- Better awareness of security vulnerabilities and mitigation
- More proactive architectural problem-solving mindset

### Future Improvements

- Multi-tenant architecture with isolated data domains
- Custom Kanban workflows and backlog support
- WebSocket-based real-time updates and push notifications

---

# END OF PRESENTATION

## Thank You for Listening!

Can Tho University (CTU)
www.ctu.edu.vn
