<?php

function assignLobbyColour(mysqli $mysqli, int $gameId, int $maxPlayers): string {
    $pawnOrder = ($maxPlayers === 2) ? ['R', 'B'] : ['R', 'B', 'G', 'Y'];
    $usedPawnsStmt = $mysqli->prepare(
        'SELECT pawn FROM game_players WHERE game_id = ?'
    );
    $usedPawnsStmt->bind_param('i', $gameId);
    $usedPawnsStmt->execute();
    $usedPawnsResult = $usedPawnsStmt->get_result();
    $usedPawns = [];

    while ($row = $usedPawnsResult->fetch_assoc()) {
        $usedPawns[$row['pawn']] = true;
    }

    $usedPawnsStmt->close();

    foreach ($pawnOrder as $pawn) {
        if (!isset($usedPawns[$pawn])) {
            return $pawn;
        }
    }

    throw new RuntimeException('No player slots are available in this lobby.', 409);
}

function getLobbyColourName(string $pawn): string {
    $colourNames = [
        'R' => 'Red',
        'B' => 'Blue',
        'G' => 'Green',
        'Y' => 'Yellow',
    ];

    if (!isset($colourNames[$pawn])) {
        throw new RuntimeException("Invalid pawn '$pawn'.", 400);
    }

    return $colourNames[$pawn];
}