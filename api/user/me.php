<?php
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== "GET") {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "Illegal request method. Use GET."]);
        exit;
    }

    require_once __DIR__ . '/../auth.php';
    require_once __DIR__ . '/../db.php';

    try {
        $stmt = $mysqli->prepare('SELECT id, username, wins, losses, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $mysqli->close();

        if (!$user) {
            session_destroy();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => "Unauthorized. Please login."]);
            exit;
        }

        echo json_encode(['success' => true, 'user' => [
                                                        'id' => (int) $user['id'],
                                                        'username' => $user['username'],
                                                        'wins' => (int) $user['wins'],
                                                        'losses' => (int) $user['losses'],
                                                        'created_at' => $user['created_at']
        ]]);
    }
    catch (mysqli_sql_exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Connection error"]);
    }
?>