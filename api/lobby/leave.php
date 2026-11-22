<?php
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Illegal request method. Use POST.']);
        exit;
    }

    require_once __DIR__ . '/../auth.php';
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../../business/lobby.php';

    try {
        $result = leaveLobby($mysqli, (int) $_SESSION['user_id']);
        $mysqli->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'lobby_closed' => $result['lobby_closed'],
            'new_host_user_id' => $result['new_host_user_id'],
        ]);
    } catch (RuntimeException $e) {
        $mysqli->close();
        http_response_code($e->getCode() ?: 400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Throwable $e) {
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error while leaving lobby.']);
    }
?>