-- 002_add_search_count_to_users.sql
ALTER TABLE users ADD COLUMN search_count INT NOT NULL DEFAULT 0;