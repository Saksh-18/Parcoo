<?php
require 'Db.php';
require '../vendor/autoload.php';  // Firebase JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'your-secret-key';  // Use a strong secret key
$data = json_decode(file_get_contents('php://input'), true); // Fetch input JSON

// Ensure JWT is provided in headers
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    echo json_encode(['error' => 'Authorization header not found']);
    exit();
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader); // Extract token from Bearer header

try {
    $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
    $userId = $decodedToken->data->userId; // Get userId from the token
} catch (Exception $e) {
    echo json_encode(['error' => 'Invalid token']);
    exit();
}

if (isset($_GET['f'])) {
    $function = $_GET['f'];
    switch ($function) {
        case 'bookParking':
            bookParking($data, $userId);  // Pass the userId extracted from the token
            break;
        case 'checkAvailability':
            checkSlotAvailability($data);
            break;
        default:
            echo json_encode(['error' => 'Invalid function']);
    }
} else {
    echo json_encode(['error' => 'No function specified']);
}

// Function to check slot availability
function checkSlotAvailability($data) {
    $db = new Db();
    $conn = $db->connect();

    $parkingId = $data['parking_id'] ?? null;
    if (!$parkingId) {
        echo json_encode(['error' => 'Missing parking ID']);
        return;
    }

    // Check for available parking slots
    $query = 'SELECT id FROM parking_slots WHERE parking_id = :parking_id AND is_available = 1 LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':parking_id', $parkingId);
    $stmt->execute();
    $availableSlot = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($availableSlot) {
        echo json_encode([
            'available' => true,
            'slot_id' => $availableSlot['id']
        ]);
    } else {
        echo json_encode(['available' => false, 'message' => 'No available slots']);
    }
}

// Function to book parking
function bookParking($data, $userId) {
    $db = new Db();
    $conn = $db->connect();

    // Required fields for booking
    $parkingId = $data['parking_id'] ?? null;
    $slotId = $data['slot_id'] ?? null;
    $startTime = $data['start_time'] ?? null;
    $endTime = $data['end_time'] ?? null;
    $paymentMethod = $data['payment_method'] ?? null;
    $amountPaid = $data['amount_paid'] ?? 0;

    if (!$parkingId || !$slotId || !$startTime || !$endTime || !$paymentMethod) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // Check if the slot is still available
    $query = 'SELECT is_available FROM parking_slots WHERE id = :slot_id AND parking_id = :parking_id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':slot_id', $slotId);
    $stmt->bindParam(':parking_id', $parkingId);
    $stmt->execute();
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slot || $slot['is_available'] == 0) {
        echo json_encode(['error' => 'Slot is no longer available']);
        return;
    }

    // Mark the slot as unavailable
    $updateSlot = 'UPDATE parking_slots SET is_available = 0 WHERE id = :slot_id';
    $stmt = $conn->prepare($updateSlot);
    $stmt->bindParam(':slot_id', $slotId);
    $stmt->execute();

    // Generate a booking token
    $bookingToken = bin2hex(random_bytes(16));

    // Insert booking record
    $query = 'INSERT INTO bookings (user_id, parking_id, slot_id, booking_token, start_time, end_time, amount_paid, payment_status, payment_method) 
              VALUES (:user_id, :parking_id, :slot_id, :booking_token, :start_time, :end_time, :amount_paid, :payment_status, :payment_method)';
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':parking_id', $parkingId);
    $stmt->bindParam(':slot_id', $slotId);
    $stmt->bindParam(':booking_token', $bookingToken);
    $stmt->bindParam(':start_time', $startTime);
    $stmt->bindParam(':end_time', $endTime);
    $stmt->bindParam(':amount_paid', $amountPaid);
    $stmt->bindParam(':payment_status', $paymentStatus = 'pending');
    $stmt->bindParam(':payment_method', $paymentMethod);

    if ($stmt->execute()) {
        echo json_encode([
            'message' => 'Booking successful',
            'booking_token' => $bookingToken,
            'slot_id' => $slotId,
            'parking_id' => $parkingId
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create booking']);
    }
}
