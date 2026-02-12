<?php

class Database {
    private $pdo;
    private $host = 'localhost';
    private $dbName = 'labs_reservation_db';
    private $username = 'root';
    private $password = '';

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }

    public function getLaboratories() {
        $stmt = $this->pdo->query("SELECT * FROM laboratories ORDER BY name");
        return $stmt->fetchAll();
    }

    public function getBookings() {
        $stmt = $this->pdo->prepare("
            SELECT b.*, l.name AS laboratory_name, l.capacity
            FROM bookings b
            JOIN laboratories l ON b.laboratory_id = l.id
            WHERE b.status = 'active'
            ORDER BY b.date, b.start_time
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function checkOverlap($laboratoryId, $date, $startTime, $endTime) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS count
            FROM bookings
            WHERE laboratory_id = ?
              AND date = ?
              AND status = 'active'
              AND (
                  (start_time < ? AND end_time > ?) OR
                  (start_time < ? AND end_time > ?) OR
                  (start_time >= ? AND end_time <= ?)
              )
        ");
        $stmt->execute([$laboratoryId, $date, $endTime, $startTime, $startTime, $endTime, $startTime, $endTime]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function createBooking($laboratoryId, $date, $startTime, $endTime) {
        if ($this->checkOverlap($laboratoryId, $date, $startTime, $endTime)) {
            throw new Exception('Laboratory is not available for the selected time range');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO bookings (laboratory_id, date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$laboratoryId, $date, $startTime, $endTime]);
        return $this->pdo->lastInsertId();
    }

    public function cancelBooking($bookingId) {
        $stmt = $this->pdo->prepare("
            UPDATE bookings
            SET status = 'cancelled', updated_at = NOW()
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$bookingId]);
        return $stmt->rowCount() > 0;
    }
}
