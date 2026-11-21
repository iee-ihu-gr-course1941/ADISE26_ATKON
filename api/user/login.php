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

    if ($password === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Password is required."]);
        exit;
    }

    try {
        $stmt = $mysqli->prepare('SELECT id, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $mysqli->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => "Login successful."]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => "Invalid credentials."]);
        }
    }
    catch (mysqli_sql_exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Connection error"]);
    }
?>