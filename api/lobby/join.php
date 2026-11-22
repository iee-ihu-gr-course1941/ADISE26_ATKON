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

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Request body must be valid JSON.']);
        exit;
    }

    $hasLobbyId = array_key_exists('lobby_id', $input);
    $hasRoomCode = array_key_exists('room_code', $input);
    if ($hasLobbyId === $hasRoomCode) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Provide either lobby_id or room_code.']);
        exit;
    }

    $lobbyId = null;
    $roomCode = null;

    if ($hasLobbyId) {
        $validatedId = filter_var($input['lobby_id'], FILTER_VALIDATE_INT);
        if ($validatedId === false || $validatedId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'lobby_id must be a positive integer.']);
            exit;
        }
        $lobbyId = $validatedId;
    } else {
        $roomCodeInput = is_string($input['room_code']) ? strtoupper(trim($input['room_code'])) : '';
        if (!preg_match('/^[A-Z]{6}$/', $roomCodeInput)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'room_code must be six letters.']);
            exit;
        }
        $roomCode = $roomCodeInput;
    }

    try {
        $lobby = joinLobby($mysqli, (int) $_SESSION['user_id'], $lobbyId, $roomCode);
        $mysqli->close();

        http_response_code(200);
        echo json_encode(['success' => true, 'lobby' => $lobby]);
    } catch (RuntimeException $e) {
        $mysqli->close();
        http_response_code($e->getCode() ?: 400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Throwable $e) {
        $mysqli->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error while joining lobby.']);
    }
?>
