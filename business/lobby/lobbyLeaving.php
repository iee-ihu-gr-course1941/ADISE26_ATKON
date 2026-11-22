<?php

require_once __DIR__ . '/lobbySupport.php';

function leaveLobby(mysqli $mysqli, int $userId): array {
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
            "SELECT g.id, g.host_user_id, g.status
             FROM game_players gp
             INNER JOIN games g ON g.id = gp.game_id
             WHERE gp.user_id = ? AND gp.status = 'active' AND g.status IN ('lobby', 'active')
             LIMIT 1
             FOR UPDATE"
        );
        $membershipStmt->bind_param('i', $userId);
        $membershipStmt->execute();
        $membership = $membershipStmt->get_result()->fetch_assoc();
        $membershipStmt->close();

        if (!$membership) {
            throw new RuntimeException('You are not in an active lobby.', 404);
        }

        if ($membership['status'] !== 'lobby') {
            throw new RuntimeException('You cannot leave after the game has started.', 409);
        }

        $gameId = (int) $membership['id'];
        $hostUserId = (int) $membership['host_user_id'];
        $playersStmt = $mysqli->prepare(
            "SELECT id, user_id
             FROM game_players
             WHERE game_id = ? AND status = 'active'
             ORDER BY id
             FOR UPDATE"
        );
        $playersStmt->bind_param('i', $gameId);
        $playersStmt->execute();
        $playersResult = $playersStmt->get_result();
        $remainingPlayers = [];
        while ($player = $playersResult->fetch_assoc()) {
            if ((int) $player['user_id'] !== $userId) {
                $remainingPlayers[] = [
                    'id' => (int) $player['id'],
                    'user_id' => (int) $player['user_id'],
                ];
            }
        }
        $playersStmt->close();

        if (!$remainingPlayers) {
            $deleteGame = $mysqli->prepare('DELETE FROM games WHERE id = ?');
            $deleteGame->bind_param('i', $gameId);
            $deleteGame->execute();
            $deleteGame->close();
            $mysqli->commit();

            return [
                'lobby_closed' => true,
                'new_host_user_id' => null,
            ];
        }

        $newHostUserId = null;
        if ($hostUserId === $userId) {
            $newHostUserId = $remainingPlayers[0]['user_id'];
            $updateHost = $mysqli->prepare(
                'UPDATE games SET host_user_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $updateHost->bind_param('ii', $newHostUserId, $gameId);
            $updateHost->execute();
            $updateHost->close();
        } else {
            $updateLobby = $mysqli->prepare(
                'UPDATE games SET updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $updateLobby->bind_param('i', $gameId);
            $updateLobby->execute();
            $updateLobby->close();
        }

        $deletePlayer = $mysqli->prepare(
            'DELETE FROM game_players WHERE game_id = ? AND user_id = ?'
        );
        $deletePlayer->bind_param('ii', $gameId, $userId);
        $deletePlayer->execute();
        $deletePlayer->close();

        $mysqli->commit();

        return [
            'lobby_closed' => false,
            'new_host_user_id' => $newHostUserId,
        ];
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}
