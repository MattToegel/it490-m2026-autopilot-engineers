-- ---------- user_warnings (US-04 AC2 final-demo) ----------
-- cao39: notifies a user when an admin flags one of their reports.
-- Separate from flight_alerts since that table's saved_flight_id
-- has a real FK to saved_flights, which doesn't apply here.
CREATE TABLE IF NOT EXISTS user_warnings
(
    warning_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_id INT,
    admin_user_id INT NOT NULL,
    warning_message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);