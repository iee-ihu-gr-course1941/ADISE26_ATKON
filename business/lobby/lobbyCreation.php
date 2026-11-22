<?php

require_once __DIR__ . '/lobbySupport.php';

function createLobby(mysqli $mysqli, int $hostUserId, int $maxPlayers, int $isPublic): array {
    cleanupExpiredLobbies($mysqli);

    $mysqli->begin_transaction();

    try {
        $userStmt = $mysqli->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
        $userStmt->bind_param('i', $hostUserId);
        $userStmt->execute();
        $userExists = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!$userExists) {
            throw new RuntimeException('Authenticated user was not found.', 401);
        }

        $checkStmt = $mysqli->prepare(
            "SELECT g.id
             FROM game_players gp
             INNER JOIN games g ON g.id = gp.game_id
             WHERE gp.user_id = ? AND gp.status = 'active' AND g.status IN ('lobby', 'active')
             LIMIT 1"
        );
        $checkStmt->bind_param('i', $hostUserId);
        $checkStmt->execute();
        $alreadyInGame = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($alreadyInGame) {
            throw new RuntimeException('You are already in an active lobby or game.', 409);
        }

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
