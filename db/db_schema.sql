-- tad46: OnTheRadar database schema
-- tad46: Rebuild/reference of the current MVP schema

-- ---------- users ----------
CREATE TABLE IF NOT EXISTS users
(
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------- logs (centralized log pipeline) ----------
CREATE TABLE IF NOT EXISTS logs
(
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- saved_flights (US-05) ----------
CREATE TABLE IF NOT EXISTS saved_flights
(
    saved_flight_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flight_number VARCHAR(20) NOT NULL,
    airline VARCHAR(50),
    departure_airport VARCHAR(80),
    arrival_airport VARCHAR(80),
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- tad46: NEEDED for AC5 soft-delete - add if not present
    -- removed_at DATETIME DEFAULT NULL,

    UNIQUE KEY unique_user_flight (user_id, flight_number),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ---------- cached_flight_data (US-02) ----------
-- tad46: Redesigned to match Xaidyn's FlightTransformer output (20 columns)
DROP TABLE IF EXISTS cached_flight_data;
CREATE TABLE cached_flight_data
(
    cached_flight_id INT AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(20) NOT NULL UNIQUE,
    `airline` VARCHAR(50),
    `status` VARCHAR(50),

    departure_airport VARCHAR(80),
    arrival_airport VARCHAR(80),

    `terminal` VARCHAR(20),
    `gate` VARCHAR(20),
    arrival_terminal VARCHAR(20),
    arrival_gate VARCHAR(20),

    scheduled_departure DATETIME,
    estimated_departure DATETIME,
    actual_departure DATETIME,
    scheduled_arrival DATETIME,
    estimated_arrival DATETIME,
    actual_arrival DATETIME,

    aircraft_model VARCHAR(80),
    aircraft_registration VARCHAR(20),

    -- tad46: derived at write time from timing fields, used for future alert diffing
    delay_minutes INT DEFAULT 0,
    cancellation_status TINYINT(1) DEFAULT 0,

    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------- airport_reports (US-03) ----------
CREATE TABLE IF NOT EXISTS airport_reports
(
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    airport_code VARCHAR(10) DEFAULT 'EWR',
    terminal VARCHAR(20),
    category VARCHAR(50) NOT NULL,
    comment_text TEXT NOT NULL,
    report_status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ---------- flight_alerts (US-05 final-demo) ----------
CREATE TABLE IF NOT EXISTS flight_alerts
(
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    saved_flight_id INT,
    flight_number VARCHAR(20) NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    alert_message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ---------- admin_activity_logs (US-04) ----------
CREATE TABLE IF NOT EXISTS admin_activity_logs
(
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    affected_user_id INT,
    affected_report_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ---------- user_warnings (US-04 AC2 final-demo) ----------
-- cao39: notifies a user when an admin flags one of their reports.
-- Separate from flight_alerts
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
