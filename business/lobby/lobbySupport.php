<?php

function cleanupExpiredLobbies(mysqli $mysqli): void {
    $mysqli->query("DELETE FROM games WHERE status = 'lobby' AND updated_at < (NOW() - INTERVAL 2 MINUTE)");
}

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
