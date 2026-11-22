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

    $hostUserId = (int) $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (isset($input['host_user_id']) && (int) $input['host_user_id'] !== $hostUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Host ID does not match authenticated user.']);
        exit;
    }

    $maxPlayers = isset($input['max_players']) ? (int) $input['max_players'] : 0;
    if (!in_array($maxPlayers, [2, 4], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'max_players must be 2 or 4.']);
        exit;
    }

    $isPublicBool = array_key_exists('is_public', $input)
        ? filter_var($input['is_public'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
        : null;

    if ($isPublicBool === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'is_public must be a boolean.']);
        exit;
    }

    try {
        $lobby = createLobby($mysqli, $hostUserId, $maxPlayers, $isPublicBool ? 1 : 0);
        $mysqli->close();

        http_response_code(201);
        echo json_encode(['success' => true, 'lobby' => $lobby]);
    } catch (RuntimeException $e) {
        $mysqli->close();
        http_response_code($e->getCode() ?: 400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Throwable $e) {
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error while creating lobby.']);
    }
?>