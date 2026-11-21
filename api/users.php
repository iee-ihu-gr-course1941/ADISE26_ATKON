<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    // Never select password_hash!
    $result = $mysqli->query("SELECT id, username, wins, losses, created_at FROM users ORDER BY wins DESC, id ASC");
    $users = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'users'   => $users
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
}