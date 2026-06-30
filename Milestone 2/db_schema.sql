-- =====================================================
-- IT490 Autopilot Engineers - Full Database Schema
-- tad46 - Complete schema for OnTheRadar flight tracker app
-- =====================================================

-- ===== Logging (existing from Milestone 1) =====
CREATE TABLE IF NOT EXISTS logs 
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_source (source),
    INDEX idx_level (level)
);

-- ===== Entity 1: Users =====
CREATE TABLE IF NOT EXISTS users 
(
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ===== Entity 2: SearchHistory =====
CREATE TABLE IF NOT EXISTS search_history 
(
    search_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    search_type VARCHAR(30) NOT NULL,           -- 'airport', 'flight_number', 'route'
    airport_code VARCHAR(10),                   -- e.g., 'EWR' (nullable for flight_number searches)
    flight_number VARCHAR(20),                  -- nullable for airport searches
    departure_airport VARCHAR(10),              -- for route searches
    arrival_airport VARCHAR(10),                -- for route searches
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_searched (user_id, searched_at)
);

-- ===== Entity 3: SavedFlights =====
CREATE TABLE IF NOT EXISTS saved_flights 
(
    saved_flight_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flight_number VARCHAR(20) NOT NULL,
    airline VARCHAR(100),
    departure_airport VARCHAR(10),
    arrival_airport VARCHAR(10),
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_flight (user_id, flight_number),
    INDEX idx_user (user_id)
);

-- ===== Entity 4: AirportReports =====
CREATE TABLE IF NOT EXISTS airport_reports 
(
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    airport_code VARCHAR(10) NOT NULL DEFAULT 'EWR',
    terminal VARCHAR(20),
    category VARCHAR(50) NOT NULL,              -- 'tsa', 'bathroom', 'accident', 'food', etc.
    comment_text TEXT NOT NULL,
    report_status VARCHAR(20) NOT NULL DEFAULT 'active',  -- 'active', 'resolved', 'flagged', 'removed'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status (report_status),
    INDEX idx_category (category),
    INDEX idx_created (created_at)
);

-- ===== Entity 5: CachedFlightData =====
CREATE TABLE IF NOT EXISTS cached_flight_data 
(
    cached_flight_id INT AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(20) NOT NULL,
    airline VARCHAR(100),
    status VARCHAR(50),                         -- 'on-time', 'delayed', 'cancelled', 'boarding', etc.
    gate VARCHAR(20),
    terminal VARCHAR(20),
    delay_minutes INT DEFAULT 0,
    cancellation_status BOOLEAN DEFAULT FALSE,
    departure_time DATETIME,
    arrival_time DATETIME,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_flight (flight_number),
    INDEX idx_status (status),
    INDEX idx_last_updated (last_updated)
);

-- ===== Entity 6: FlightAlerts =====
CREATE TABLE IF NOT EXISTS flight_alerts 
(
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    saved_flight_id INT NOT NULL,
    flight_number VARCHAR(20) NOT NULL,
    alert_type VARCHAR(50) NOT NULL,            -- 'delay', 'cancellation', 'gate_change', 'terminal_change'
    alert_message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (saved_flight_id) REFERENCES saved_flights(saved_flight_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
);

-- ===== Entity 7: AdminActivityLogs =====
CREATE TABLE IF NOT EXISTS admin_activity_logs 
(
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,           -- 'role_change', 'report_removed', 'warning_issued', etc.
    affected_user_id INT,                       -- nullable (not all actions target a user)
    affected_report_id INT,                     -- nullable (not all actions target a report)
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (affected_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (affected_report_id) REFERENCES airport_reports(report_id) ON DELETE SET NULL,
    INDEX idx_admin (admin_user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
);