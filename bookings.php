<?php


// Sets response format to JSON
header('Content-Type: application/json');

// Allows requests from any origin (CORS policy)
header('Access-Control-Allow-Origin: *');

// Defines allowed HTTP methods
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

// Defines allowed request headers
header('Access-Control-Allow-Headers: Content-Type');

// Includes the database class
require_once 'database.php';

// Detects HTTP method and requested action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // Creates database instance
    $db = new Database();

    // Routes request based on HTTP method
    switch ($method) {
        case 'GET':
            handleGet($action, $db);
            break;
        case 'POST':
            handlePost($action, $db);
            break;
        case 'DELETE':
            handleDelete($action, $db);
            break;
        case 'OPTIONS':
            http_response_code(200);
            exit;
        default:
            throw new Exception('Method not allowed', 405);
    }
} catch (Exception $e) {
    // Global error handler returning JSON error response
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}


function handleGet($action, $db) {
    switch ($action) {
        case 'laboratories':
            getLaboratories($db);
            break;
        case 'bookings':
            getBookings($db);
            break;
        case 'availability':
            checkAvailability($db);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}


function handlePost($action, $db) {
    switch ($action) {
        case 'book':
            createBooking($db);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}


function handleDelete($action, $db) {
    switch ($action) {
        case 'cancel':
            cancelBooking($db);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}


function getLaboratories($db) {
    $laboratories = $db->getLaboratories();
    echo json_encode([
        'status' => 'success',
        'data' => $laboratories
    ]);
}


function getBookings($db) {
    $bookings = $db->getBookings();
    echo json_encode([
        'status' => 'success',
        'data' => $bookings
    ]);
}


function checkAvailability($db) {
    $laboratoryId = $_GET['laboratory_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $startTime = $_GET['start_time'] ?? null;
    $endTime = $_GET['end_time'] ?? null;

    // Validates required parameters
    if (!$laboratoryId || !$date || !$startTime || !$endTime) {
        throw new Exception('Missing required parameters: laboratory_id, date, start_time, end_time', 400);
    }

    // Validates numeric laboratory ID
    if (!is_numeric($laboratoryId)) {
        throw new Exception('laboratory_id must be numeric', 400);
    }

    // Validates date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format (YYYY-MM-DD)', 400);
    }

    // Validates time format
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
        throw new Exception('Invalid time format (HH:MM or HH:MM:SS)', 400);
    }

    // Calls database to check for overlapping bookings
    $available = !$db->checkOverlap($laboratoryId, $date, $startTime, $endTime);

    echo json_encode([
        'status' => 'success',
        'available' => $available,
        'message' => $available ? 'Available' : 'Not available'
    ]);
}


function createBooking($db) {
    // Reads JSON input from request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    // Validates required fields
    $required = ['laboratory_id', 'date', 'start_time', 'end_time'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Required field missing: $field", 400);
        }
    }

    // Field validations
    if (!is_numeric($input['laboratory_id'])) {
        throw new Exception('laboratory_id must be numeric', 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date'])) {
        throw new Exception('Invalid date format (YYYY-MM-DD)', 400);
    }

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $input['start_time']) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $input['end_time'])) {
        throw new Exception('Invalid time format (HH:MM or HH:MM:SS)', 400);
    }

    // Prevents booking past dates
    $date = new DateTime($input['date']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($date < $today) {
        throw new Exception('Cannot book for past dates', 400);
    }

    // Validates time range
    $startTime = new DateTime($input['start_time']);
    $endTime = new DateTime($input['end_time']);

    if ($startTime >= $endTime) {
        throw new Exception('End time must be after start time', 400);
    }

    try {
        // Calls database method to create booking
        $bookingId = $db->createBooking(
            $input['laboratory_id'],
            $input['date'],
            $input['start_time'],
            $input['end_time']
        );

        // Returns success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking created successfully',
            'data' => [
                'booking_id' => $bookingId,
                'laboratory_id' => $input['laboratory_id'],
                'date' => $input['date'],
                'start_time' => $input['start_time'],
                'end_time' => $input['end_time']
            ]
        ]);
    } catch (Exception $e) {
        throw new Exception('Error creating booking: ' . $e->getMessage(), 500);
    }
}

function cancelBooking($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['booking_id'])) {
        throw new Exception('booking_id required', 400);
    }

    if (!is_numeric($input['booking_id'])) {
        throw new Exception('booking_id must be numeric', 400);
    }

    try {
        $result = $db->cancelBooking($input['booking_id']);

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Booking cancelled successfully'
            ]);
        } else {
            throw new Exception('Booking not found or already cancelled', 404);
        }
    } catch (Exception $e) {
        throw new Exception('Error cancelling booking: ' . $e->getMessage(), 500);
    }
}
