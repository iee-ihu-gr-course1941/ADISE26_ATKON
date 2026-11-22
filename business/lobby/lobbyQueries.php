<?php

require_once __DIR__ . '/lobbySupport.php';
require_once __DIR__ . '/lobbyAssignColours.php';

function getUserLobby(mysqli $mysqli, int $userId): ?array {
    $stmt = $mysqli->prepare(
        'SELECT
            g.id,
            g.room_code,
            g.host_user_id,
            host.username AS host_username,
            g.max_players,
            g.is_public,
            g.status,
            COUNT(members.user_id) AS player_count
         FROM games g
         INNER JOIN users host ON host.id = g.host_user_id
         INNER JOIN game_players membership
            ON membership.game_id = g.id
           AND membership.user_id = ?
           AND membership.status = "active"
         LEFT JOIN game_players members
            ON members.game_id = g.id
           AND members.status = "active"
         WHERE g.status IN ("lobby", "active")
         GROUP BY g.id, g.room_code, g.host_user_id, host.username, g.max_players, g.is_public, g.status
         LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $playersStmt = $mysqli->prepare(
        "SELECT u.username, gp.pawn
         FROM game_players gp
         INNER JOIN users u ON u.id = gp.user_id
         WHERE gp.game_id = ? AND gp.status = 'active'
         ORDER BY gp.turn_order"
    );
    $gameId = (int) $row['id'];
    $playersStmt->bind_param('i', $gameId);
    $playersStmt->execute();
    $playersResult = $playersStmt->get_result();
    $players = [];

    while ($player = $playersResult->fetch_assoc()) {
        $players[] = [
            'username' => $player['username'],
            'pawn' => $player['pawn'],
            'colour' => getLobbyColourName($player['pawn']),
        ];
    }

    $playersStmt->close();

    $maxPlayers = (int) $row['max_players'];
    $playerCount = (int) $row['player_count'];

    return [
        'id'               => (int) $row['id'],
        'room_code'        => $row['room_code'],
        'host_user_id'     => (int) $row['host_user_id'],
        'host_username'    => $row['host_username'],
        'max_players'      => $maxPlayers,
        'player_count'     => $playerCount,
        'available_slots'  => max(0, $maxPlayers - $playerCount),
        'is_public'        => (bool) $row['is_public'],
        'status'           => $row['status'],
        'players'          => $players,
    ];
}

function searchPublicLobbies(mysqli $mysqli): array {
    cleanupExpiredLobbies($mysqli);

    $sql =
        'SELECT
            g.id,
            g.room_code,
            g.host_user_id,
            g.max_players,
            g.status,
            host.username AS host_username,
            COUNT(CASE WHEN gp.status = "active" THEN 1 END) AS player_count
         FROM games g
         INNER JOIN users host ON host.id = g.host_user_id
         LEFT JOIN game_players gp ON gp.game_id = g.id
         WHERE g.is_public = 1
           AND g.status IN ("lobby", "active")
         GROUP BY g.id, g.room_code, g.host_user_id, g.max_players, g.status, host.username
         HAVING g.status = "active" OR player_count < g.max_players
         ORDER BY g.updated_at DESC';

    $result = $mysqli->query($sql);
    $lobbies = [];

    while ($row = $result->fetch_assoc()) {
        $lobbies[] = [
            'id'            => (int) $row['id'],
            'room_code'     => $row['room_code'],
            'host_user_id'  => (int) $row['host_user_id'],
            'host_username' => $row['host_username'],
            'max_players'   => (int) $row['max_players'],
            'player_count'  => (int) $row['player_count'],
            'status'        => $row['status'],
        ];
    }

    return $lobbies;
}
