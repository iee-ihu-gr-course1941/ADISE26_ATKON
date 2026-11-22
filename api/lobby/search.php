<?php
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Illegal request method. Use GET.']);
        exit;
    }

    require_once __DIR__ . '/../auth.php';
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../../business/lobby.php';

    try {
        $userLobby = getUserLobby($mysqli, (int) $_SESSION['user_id']);
        $lobbies = searchPublicLobbies($mysqli);
        $mysqli->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user_lobby' => $userLobby,
            'lobbies' => $lobbies,
        ]);
    } catch (Throwable $e) {
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error while searching lobbies.']);
    }
?>