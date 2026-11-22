-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 20 Νοε 2026 στις 20:56:54
-- Έκδοση διακομιστή: 10.11.16-MariaDB
-- Έκδοση PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `quoridor`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `boardinit`
--

CREATE TABLE `boardinit` (
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `pawn` enum('R','B','G','Y') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `boardinit`
--

INSERT INTO `boardinit` (`x`, `y`, `pawn`) VALUES
(4, 0, 'R'),
(4, 8, 'B'),
(0, 4, 'G'),
(8, 4, 'Y');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `room_code` varchar(6) NOT NULL,
  `host_user_id` int(11) NOT NULL,
  `max_players` tinyint(1) NOT NULL DEFAULT 2,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('lobby','active','finished') NOT NULL DEFAULT 'lobby',
  `current_turn_pawn` enum('R','B','G','Y') DEFAULT 'R',
  `winner_pawn` enum('R','B','G','Y') DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `game_kick_votes`
--

CREATE TABLE `game_kick_votes` (
  `game_id` int(11) NOT NULL,
  `target_pawn` enum('R','B','G','Y') NOT NULL,
  `voter_pawn` enum('R','B','G','Y') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `game_players`
--

CREATE TABLE `game_players` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pawn` enum('R','B','G','Y') NOT NULL,
  `x` tinyint(1) NOT NULL,
  `y` tinyint(1) NOT NULL,
  `walls_left` tinyint(2) NOT NULL DEFAULT 10,
  `is_ready` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','kicked') NOT NULL DEFAULT 'active',
  `turn_order` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `game_walls`
--

CREATE TABLE `game_walls` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `x` tinyint(1) NOT NULL,
  `y` tinyint(1) NOT NULL,
  `orientation` enum('H','V') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `wins` int(11) NOT NULL DEFAULT 0,
  `losses` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `wallslotsinit`
--

CREATE TABLE `wallslotsinit` (
  `x` tinyint(1) NOT NULL,
  `y` tinyint(1) NOT NULL,
  `orientation` enum('H','V') NOT NULL,
  `is_legal` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `wallslotsinit`
--

INSERT INTO `wallslotsinit` (`x`, `y`, `orientation`, `is_legal`) VALUES
(0, 0, 'H', 1),
(0, 0, 'V', 1),
(0, 1, 'H', 1),
(0, 1, 'V', 1),
(0, 2, 'H', 1),
(0, 2, 'V', 1),
(0, 3, 'H', 1),
(0, 3, 'V', 1),
(0, 4, 'H', 1),
(0, 4, 'V', 1),
(0, 5, 'H', 1),
(0, 5, 'V', 1),
(0, 6, 'H', 1),
(0, 6, 'V', 1),
(0, 7, 'H', 1),
(0, 7, 'V', 1),
(0, 8, 'H', 0),
(0, 8, 'V', 0),
(1, 0, 'H', 1),
(1, 0, 'V', 1),
(1, 1, 'H', 1),
(1, 1, 'V', 1),
(1, 2, 'H', 1),
(1, 2, 'V', 1),
(1, 3, 'H', 1),
(1, 3, 'V', 1),
(1, 4, 'H', 1),
(1, 4, 'V', 1),
(1, 5, 'H', 1),
(1, 5, 'V', 1),
(1, 6, 'H', 1),
(1, 6, 'V', 1),
(1, 7, 'H', 1),
(1, 7, 'V', 1),
(1, 8, 'H', 0),
(1, 8, 'V', 0),
(2, 0, 'H', 1),
(2, 0, 'V', 1),
(2, 1, 'H', 1),
(2, 1, 'V', 1),
(2, 2, 'H', 1),
(2, 2, 'V', 1),
(2, 3, 'H', 1),
(2, 3, 'V', 1),
(2, 4, 'H', 1),
(2, 4, 'V', 1),
(2, 5, 'H', 1),
(2, 5, 'V', 1),
(2, 6, 'H', 1),
(2, 6, 'V', 1),
(2, 7, 'H', 1),
(2, 7, 'V', 1),
(2, 8, 'H', 0),
(2, 8, 'V', 0),
(3, 0, 'H', 1),
(3, 0, 'V', 1),
(3, 1, 'H', 1),
(3, 1, 'V', 1),
(3, 2, 'H', 1),
(3, 2, 'V', 1),
(3, 3, 'H', 1),
(3, 3, 'V', 1),
(3, 4, 'H', 1),
(3, 4, 'V', 1),
(3, 5, 'H', 1),
(3, 5, 'V', 1),
(3, 6, 'H', 1),
(3, 6, 'V', 1),
(3, 7, 'H', 1),
(3, 7, 'V', 1),
(3, 8, 'H', 0),
(3, 8, 'V', 0),
(4, 0, 'H', 1),
(4, 0, 'V', 1),
(4, 1, 'H', 1),
(4, 1, 'V', 1),
(4, 2, 'H', 1),
(4, 2, 'V', 1),
(4, 3, 'H', 1),
(4, 3, 'V', 1),
(4, 4, 'H', 1),
(4, 4, 'V', 1),
(4, 5, 'H', 1),
(4, 5, 'V', 1),
(4, 6, 'H', 1),
(4, 6, 'V', 1),
(4, 7, 'H', 1),
(4, 7, 'V', 1),
(4, 8, 'H', 0),
(4, 8, 'V', 0),
(5, 0, 'H', 1),
(5, 0, 'V', 1),
(5, 1, 'H', 1),
(5, 1, 'V', 1),
(5, 2, 'H', 1),
(5, 2, 'V', 1),
(5, 3, 'H', 1),
(5, 3, 'V', 1),
(5, 4, 'H', 1),
(5, 4, 'V', 1),
(5, 5, 'H', 1),
(5, 5, 'V', 1),
(5, 6, 'H', 1),
(5, 6, 'V', 1),
(5, 7, 'H', 1),
(5, 7, 'V', 1),
(5, 8, 'H', 0),
(5, 8, 'V', 0),
(6, 0, 'H', 1),
(6, 0, 'V', 1),
(6, 1, 'H', 1),
(6, 1, 'V', 1),
(6, 2, 'H', 1),
(6, 2, 'V', 1),
(6, 3, 'H', 1),
(6, 3, 'V', 1),
(6, 4, 'H', 1),
(6, 4, 'V', 1),
(6, 5, 'H', 1),
(6, 5, 'V', 1),
(6, 6, 'H', 1),
(6, 6, 'V', 1),
(6, 7, 'H', 1),
(6, 7, 'V', 1),
(6, 8, 'H', 0),
(6, 8, 'V', 0),
(7, 0, 'H', 1),
(7, 0, 'V', 1),
(7, 1, 'H', 1),
(7, 1, 'V', 1),
(7, 2, 'H', 1),
(7, 2, 'V', 1),
(7, 3, 'H', 1),
(7, 3, 'V', 1),
(7, 4, 'H', 1),
(7, 4, 'V', 1),
(7, 5, 'H', 1),
(7, 5, 'V', 1),
(7, 6, 'H', 1),
(7, 6, 'V', 1),
(7, 7, 'H', 1),
(7, 7, 'V', 1),
(7, 8, 'H', 0),
(7, 8, 'V', 0),
(8, 0, 'H', 0),
(8, 0, 'V', 0),
(8, 1, 'H', 0),
(8, 1, 'V', 0),
(8, 2, 'H', 0),
(8, 2, 'V', 0),
(8, 3, 'H', 0),
(8, 3, 'V', 0),
(8, 4, 'H', 0),
(8, 4, 'V', 0),
(8, 5, 'H', 0),
(8, 5, 'V', 0),
(8, 6, 'H', 0),
(8, 6, 'V', 0),
(8, 7, 'H', 0),
(8, 7, 'V', 0),
(8, 8, 'H', 0),
(8, 8, 'V', 0);

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `boardinit`
--
ALTER TABLE `boardinit`
  ADD PRIMARY KEY (`pawn`) USING BTREE;

--
-- Ευρετήρια για πίνακα `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`),
  ADD KEY `host_user_id` (`host_user_id`);

--
-- Ευρετήρια για πίνακα `game_kick_votes`
--
ALTER TABLE `game_kick_votes`
  ADD PRIMARY KEY (`game_id`,`target_pawn`,`voter_pawn`);

--
-- Ευρετήρια για πίνακα `game_players`
--
ALTER TABLE `game_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_game_pawn` (`game_id`,`pawn`),
  ADD UNIQUE KEY `unique_game_user` (`game_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `game_walls`
--
ALTER TABLE `game_walls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wall_pos` (`game_id`,`x`,`y`,`orientation`);

--
-- Ευρετήρια για πίνακα `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Ευρετήρια για πίνακα `wallslotsinit`
--
ALTER TABLE `wallslotsinit`
  ADD PRIMARY KEY (`x`,`y`,`orientation`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT για πίνακα `game_players`
--
ALTER TABLE `game_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT για πίνακα `game_walls`
--
ALTER TABLE `game_walls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT για πίνακα `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_ibfk_1` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `game_kick_votes`
--
ALTER TABLE `game_kick_votes`
  ADD CONSTRAINT `game_kick_votes_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `game_players`
--
ALTER TABLE `game_players`
  ADD CONSTRAINT `game_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_players_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `game_walls`
--
ALTER TABLE `game_walls`
  ADD CONSTRAINT `game_walls_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
