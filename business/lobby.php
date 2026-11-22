<?php

function cleanupExpiredLobbies(mysqli $mysqli): void {
    $mysqli->query("DELETE FROM games WHERE status = 'lobby' AND updated_at < (NOW() - INTERVAL 2 MINUTE)");
}

//Generates room code
function generateRoomCode(): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $alphabet[random_int(0, 25)];
    }
    return $code;
}

function getPawnStartingConfig(mysqli $mysqli, string $pawn, int $maxPlayers): array {
    $stmt = $mysqli->prepare('SELECT x, y FROM boardinit WHERE pawn = ? LIMIT 1');
    $stmt->bind_param('s', $pawn);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new RuntimeException("Starting position not found in boardinit for pawn '$pawn'.", 500);
    }

    $turnOrders = [
        'R' => 1,
        'B' => 2,
        'G' => 3,
        'Y' => 4,
    ];

    if (!isset($turnOrders[$pawn])) {
        throw new RuntimeException("Invalid pawn '$pawn'.", 400);
    }

    return [
        'x'          => (int) $row['x'],
        'y'          => (int) $row['y'],
        'turn_order' => $turnOrders[$pawn],
        'walls_left' => ($maxPlayers === 4) ? 5 : 10,
    ];
}

function createLobby(mysqli $mysqli, int $hostUserId, int $maxPlayers, int $isPublic): array {
    cleanupExpiredLobbies($mysqli);

    // Check if user is already in an active lobby or game
    $checkStmt = $mysqli->prepare(
        "SELECT g.id
         FROM game_players gp
         INNER JOIN games g ON g.id = gp.game_id
         WHERE gp.user_id = ? AND gp.status = 'active' AND g.status IN ('lobby', 'active')
         LIMIT 1"
    );
    $checkStmt->bind_param('i', $hostUserId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_assoc()) {
        $checkStmt->close();
        throw new RuntimeException('You are already in an active lobby or game.', 409);
    }
    $checkStmt->close();

    $mysqli->begin_transaction();

    try {
        $gameId = null;
        $roomCode = '';
        $insertGame = $mysqli->prepare(
            "INSERT INTO games (room_code, host_user_id, max_players, is_public, status) VALUES (?, ?, ?, ?, 'lobby')"
        );

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $roomCode = generateRoomCode(6);
            $insertGame->bind_param('siii', $roomCode, $hostUserId, $maxPlayers, $isPublic);

            try {
                $insertGame->execute();
                $gameId = $mysqli->insert_id;
                break;
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() !== 1062 || $attempt === 4) {
                    throw $e;
                }
            }
        }
        $insertGame->close();

        $hostPawn = 'R';
        $config = getPawnStartingConfig($mysqli, $hostPawn, $maxPlayers);
        $isReady = 0;
        $playerStatus = 'active';

        $insertPlayer = $mysqli->prepare(
            'INSERT INTO game_players (game_id, user_id, pawn, x, y, walls_left, is_ready, status, turn_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertPlayer->bind_param(
            'iisiiiisi',
            $gameId,
            $hostUserId,
            $hostPawn,
            $config['x'],
            $config['y'],
            $config['walls_left'],
            $isReady,
            $playerStatus,
            $config['turn_order']
        );
        $insertPlayer->execute();
        $insertPlayer->close();

        $mysqli->commit();

        return [
            'id'           => $gameId,
            'room_code'    => $roomCode,
            'host_user_id' => $hostUserId,
            'max_players'  => $maxPlayers,
            'is_public'    => (bool) $isPublic,
            'status'       => 'lobby',
            'pawn'         => $hostPawn,
            'walls_left'   => $config['walls_left'],
            'expires_in'   => 120
        ];
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}