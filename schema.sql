-- Academic Laboratory Reservation System
-- SQL Schema for MySQL
-- Database: labs_reservation_db

CREATE DATABASE IF NOT EXISTS labs_reservation_db;
USE labs_reservation_db;

-- Table for laboratories
CREATE TABLE IF NOT EXISTS laboratories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    laboratory_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('active', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (laboratory_id) REFERENCES laboratories(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_bookings_laboratory_date ON bookings(laboratory_id, date);
CREATE INDEX idx_bookings_status ON bookings(status);

-- Insert sample laboratories
INSERT INTO laboratories (name, description, capacity) VALUES
('Computer Lab 1', 'General purpose computer laboratory with 25 workstations', 25),
('Electronics Lab', 'Laboratory equipped for electronics and circuit design', 20),
('Physics Lab', 'Physics laboratory with experimental equipment', 15),
('Chemistry Lab', 'Chemistry laboratory with fume hoods and safety equipment', 18),
('Biology Lab', 'Biology laboratory with microscopes and incubators', 22)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    capacity = VALUES(capacity);

-- Insert sample bookings
INSERT INTO bookings (laboratory_id, date, start_time, end_time, status) VALUES
(1, '2024-12-01', '09:00:00', '11:00:00', 'active'),
(2, '2024-12-02', '14:00:00', '16:00:00', 'active')
ON DUPLICATE KEY UPDATE
    date = VALUES(date),
    start_time = VALUES(start_time),
    end_time = VALUES(end_time),
    status = VALUES(status);

-- Show created tables
SELECT 'Tables created successfully' AS message;
SELECT 'Laboratories' AS table_name, COUNT(*) AS records FROM laboratories;
SELECT 'Bookings' AS table_name, COUNT(*) AS records FROM bookings;
