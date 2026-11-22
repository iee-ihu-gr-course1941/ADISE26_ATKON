<?php

require_once __DIR__ . '/lobbySupport.php';
require_once __DIR__ . '/lobbyAssignColours.php';

function joinLobby(mysqli $mysqli, int $userId, ?int $lobbyId, ?string $roomCode): array {
    cleanupExpiredLobbies($mysqli);
    $mysqli->begin_transaction();

    try {
        $userStmt = $mysqli->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userExists = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!$userExists) {
            throw new RuntimeException('Authenticated user was not found.', 401);
        }

        $membershipStmt = $mysqli->prepare(
            "SELECT g.id
             FROM game_players gp
             INNER JOIN games g ON g.id = gp.game_id
             WHERE gp.user_id = ? AND gp.status = 'active' AND g.status IN ('lobby', 'active')
             LIMIT 1"
        );
        $membershipStmt->bind_param('i', $userId);
        $membershipStmt->execute();
        $existingMembership = $membershipStmt->get_result()->fetch_assoc();
        $membershipStmt->close();

        if ($existingMembership) {
            throw new RuntimeException('You are already in an active lobby or game.', 409);
        }

        if ($lobbyId !== null) {
            $lobbyStmt = $mysqli->prepare(
                "SELECT id, room_code, host_user_id, max_players, is_public, status
                 FROM games
                 WHERE id = ? AND status = 'lobby'
                 FOR UPDATE"
            );
            $lobbyStmt->bind_param('i', $lobbyId);
        } else {
            $lobbyStmt = $mysqli->prepare(
                "SELECT id, room_code, host_user_id, max_players, is_public, status
                 FROM games
                 WHERE room_code = ? AND status = 'lobby'
                 FOR UPDATE"
            );
            $lobbyStmt->bind_param('s', $roomCode);
        }

        $lobbyStmt->execute();
        $lobby = $lobbyStmt->get_result()->fetch_assoc();
        $lobbyStmt->close();

        if (!$lobby || ($lobbyId !== null && !(bool) $lobby['is_public'])) {
            throw new RuntimeException('Lobby not found or is not accepting players.', 404);
        }

        $gameId = (int) $lobby['id'];
        $maxPlayers = (int) $lobby['max_players'];

        $kickedStmt = $mysqli->prepare(
            "SELECT status FROM game_players WHERE game_id = ? AND user_id = ? LIMIT 1"
        );
        $kickedStmt->bind_param('ii', $gameId, $userId);
        $kickedStmt->execute();
        $priorMembership = $kickedStmt->get_result()->fetch_assoc();
        $kickedStmt->close();

        if ($priorMembership && $priorMembership['status'] === 'kicked') {
            throw new RuntimeException('You cannot rejoin a lobby you were removed from.', 409);
        }

        $countStmt = $mysqli->prepare(
            "SELECT COUNT(*) AS player_count
             FROM game_players
             WHERE game_id = ? AND status = 'active'"
        );
        $countStmt->bind_param('i', $gameId);
        $countStmt->execute();
        $playerCount = (int) $countStmt->get_result()->fetch_assoc()['player_count'];
        $countStmt->close();

        if ($playerCount >= $maxPlayers) {
            throw new RuntimeException('This lobby is full.', 409);
        }

        $pawn = assignLobbyColour($mysqli, $gameId, $maxPlayers);
        $config = getPawnStartingConfig($mysqli, $pawn, $maxPlayers);
        $isReady = 0;
        $playerStatus = 'active';
        $insertPlayer = $mysqli->prepare(
            'INSERT INTO game_players (game_id, user_id, pawn, x, y, walls_left, is_ready, status, turn_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertPlayer->bind_param(
            'iisiiiisi',
            $gameId,
            $userId,
            $pawn,
            $config['x'],
            $config['y'],
            $config['walls_left'],
            $isReady,
            $playerStatus,
            $config['turn_order']
        );
        $insertPlayer->execute();
        $insertPlayer->close();

        $updateLobby = $mysqli->prepare('UPDATE games SET updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $updateLobby->bind_param('i', $gameId);
        $updateLobby->execute();
        $updateLobby->close();

        $mysqli->commit();

        return [
            'id'               => $gameId,
            'room_code'        => $lobby['room_code'],
            'host_user_id'     => (int) $lobby['host_user_id'],
            'max_players'      => $maxPlayers,
            'player_count'     => $playerCount + 1,
            'available_slots'  => $maxPlayers - $playerCount - 1,
            'status'           => $lobby['status'],
            'pawn'             => $pawn,
            'walls_left'       => $config['walls_left'],
        ];
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}
