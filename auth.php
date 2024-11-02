<?php
require 'Db.php';
require '../vendor/autoload.php';  // Include Firebase JWT library

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'your-secret-key';  // Change this to a strong secret key

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (isset($_GET['f'])) {
    $function = $_GET['f'];

    switch ($function) {
        case 'login':
            login($data);  // Pass the parsed JSON data
            break;
        case 'register':
            register($data);  // Pass the parsed JSON data
            break;
        default:
            echo json_encode(['error' => 'Invalid function']);
    }
} else {
    echo json_encode(['error' => 'No function specified']);
}

function register($data) {
    global $secretKey;

    $db = new Db();
    $conn = $db->connect();

    // Get the user data from the parsed JSON input
    $name = $data['name'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$name || !$email || !$password) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // Check if user already exists
    $query = 'SELECT * FROM users WHERE email = :email';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Email already registered']);
        return;
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user into the database
    $query = 'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);

    if ($stmt->execute()) {
        // On successful registration, generate a JWT token
        $userId = $conn->lastInsertId();
        $payload = [
            'iss' => "http://your-domain.com",   // Issuer
            'aud' => "http://your-domain.com",   // Audience
            'iat' => time(),                    // Issued at time
            'nbf' => time(),                    // Not before time
            'exp' => time() + (60*60*24*365),   // Token expiration time (1 year)
            'data' => [
                'userId' => $userId,
                'email' => $email
            ]
        ];

        // Encode payload to JWT
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        echo json_encode([
            'message' => 'Registration successful',
            'token' => $jwt
        ]);
    } else {
        echo json_encode(['error' => 'Registration failed']);
    }
}

function login($data) {
    global $secretKey;

    $db = new Db();
    $conn = $db->connect();

    // Get the user data from the parsed JSON input
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        echo json_encode(['error' => 'Missing email or password']);
        return;
    }

    // Fetch user
    $query = 'SELECT * FROM users WHERE email = :email';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // User is authenticated, now generate the JWT token
        $payload = [
            'iss' => "http://your-domain.com",   // Issuer
            'aud' => "http://your-domain.com",   // Audience
            'iat' => time(),                    // Issued at time
            'nbf' => time(),                    // Not before time
            'exp' => time() + (60*60*24*365),   // Token expiration time (1 year)
            'data' => [
                'userId' => $user['id'],
                'email' => $user['email']
            ]
        ];

        
        // Encode payload to JWT
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        echo json_encode([
            'message' => 'Login successful',
            'token' => $jwt
        ]);
    } else {
        echo json_encode(['error' => 'Invalid credentials']);
    }
}
?>
