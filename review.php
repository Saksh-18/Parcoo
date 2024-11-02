<?php
require 'Db.php';

// Constants
define('EARTH_RADIUS', 6371000); // Earth radius in meters

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (isset($_GET['f'])) {
    $function = $_GET['f'];

    switch ($function) {
        case 'index':
            index();  // View all parkings
            break;
        case 'nearest':
            nearest($data);  // Show nearest parking based on user location
            break;
        case 'add_review':
            addReview($data);  // Add review for a parking
            break;
        case 'get_reviews':
            getReviews($data);  // Get all reviews for a specific parking
            break;
        default:
            echo json_encode(['error' => 'Invalid function']);
    }
} else {
    echo json_encode(['error' => 'No function specified']);
}

// Fetch and display all parkings
function index() {
    $db = new Db();
    $conn = $db->connect();

    $query = 'SELECT id, name, address, price_per_hour FROM parking';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $parkings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($parkings) {
        echo json_encode(['parkings' => $parkings]);
    } else {
        echo json_encode(['error' => 'No parkings available']);
    }
}

// Fetch nearest parkings based on user longitude and latitude
function nearest($data) {
    if (!isset($data['longitude']) || !isset($data['latitude'])) {
        echo json_encode(['error' => 'Missing user location (longitude and latitude)']);
        return;
    }

    $userLongitude = $data['longitude'];
    $userLatitude = $data['latitude'];

    $db = new Db();
    $conn = $db->connect();

    // Fetch nearest 5 parkings based on the Haversine formula
    $query = "
        SELECT id, name, address, price_per_hour, longitude, latitude,
        ( 
            " . EARTH_RADIUS . " * ACOS( 
                COS(RADIANS(:user_latitude)) * COS(RADIANS(latitude)) * 
                COS(RADIANS(longitude) - RADIANS(:user_longitude)) + 
                SIN(RADIANS(:user_latitude)) * SIN(RADIANS(latitude)) 
            )
        ) AS distance
        FROM parking
        HAVING distance < 5000  -- Show parkings within 5 kilometers
        ORDER BY distance ASC
        LIMIT 5
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_latitude', $userLatitude);
    $stmt->bindParam(':user_longitude', $userLongitude);
    $stmt->execute();
    $parkings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($parkings) {
        $response = [];
        foreach ($parkings as $parking) {
            // Distance in meters
            $distanceInMeters = $parking['distance'];

            // Convert distance to kilometers
            $distanceInKilometers = $distanceInMeters / 1000;

            // Calculate time taken to reach by car at 60 km/h
            $timeInHours = $distanceInKilometers / 60;
            $timeInMinutes = $timeInHours * 60;

            // Add to the response
            $response[] = [
                'name' => $parking['name'],
                'address' => $parking['address'],
                'distance_meters' => round($distanceInMeters, 2),
                'distance_kilometers' => round($distanceInKilometers, 2),
                'time_by_car_minutes' => round($timeInMinutes, 2),
                'price_per_hour' => $parking['price_per_hour']
            ];
        }

        echo json_encode(['nearest_parkings' => $response]);
    } else {
        echo json_encode(['error' => 'No nearby parkings found']);
    }
}

// Add a review for a parking
function addReview($data) {
    if (!isset($data['user_id']) || !isset($data['parking_id']) || !isset($data['rating']) || !isset($data['comment'])) {
        echo json_encode(['error' => 'Missing required parameters (user_id, parking_id, rating, comment)']);
        return;
    }

    $userId = $data['user_id'];
    $parkingId = $data['parking_id'];
    $rating = $data['rating'];
    $comment = $data['comment'];

    $db = new Db();
    $conn = $db->connect();

    $query = "INSERT INTO reviews (user_id, parking_id, rating, comment, created_at) VALUES (:user_id, :parking_id, :rating, :comment, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':parking_id', $parkingId);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':comment', $comment);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Review added successfully']);
    } else {
        echo json_encode(['error' => 'Failed to add review']);
    }
}

// Get all reviews for a specific parking
function getReviews($data) {
    if (!isset($data['parking_id'])) {
        echo json_encode(['error' => 'Missing required parameter (parking_id)']);
        return;
    }

    $parkingId = $data['parking_id'];

    $db = new Db();
    $conn = $db->connect();

    $query = "SELECT id, user_id, parking_id, rating, comment, created_at FROM reviews WHERE parking_id = :parking_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':parking_id', $parkingId);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($reviews) {
        echo json_encode(['reviews' => $reviews]);
    } else {
        echo json_encode(['error' => 'No reviews found for this parking']);
    }
}
?>
