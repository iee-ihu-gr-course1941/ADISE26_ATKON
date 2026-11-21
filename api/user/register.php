<?php
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "Illegal request method. Use POST."]);
        exit;
    }

    require_once __DIR__ . '/../db.php';

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }

    $username = is_string($input['username'] ?? null) ? trim($input['username']) : '';
    $password = is_string($input['password'] ?? null) ? $input['password'] : '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Username must be 3-30 characters (letters, numbers, underscores only)."]);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Password must be at least 6 characters long."]);
        exit;
    }

    try {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            $mysqli->close();
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => "Username already exists."]);
            exit;
        }
        $stmt->close();

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
        $stmt->bind_param('ss', $username, $hashedPassword);
        $stmt->execute();
        $newUserId = (int) $stmt->insert_id;
        $stmt->close();
        $mysqli->close();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => "User registered successfully."]);
    }
    catch (mysqli_sql_exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Connection error"]);
    }
?>