-- Add soft-delete archive support for projects and tasks
-- Run once on existing databases.

USE kanban_db;

ALTER TABLE projects
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER project_code,
    ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER is_archived,
    ADD INDEX idx_project_archived (is_archived);

ALTER TABLE tasks
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER attachment_path,
    ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER is_archived,
    ADD INDEX idx_task_archived (is_archived),
    ADD INDEX idx_project_archived (project_id, is_archived);
