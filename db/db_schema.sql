-- db_schema.sql
-- Schema for the IT490 centralized logging database
-- Applied automatically by setup-db.sh if present

CREATE TABLE IF NOT EXISTS logs 
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
