-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: projecty_datenbank
-- Erstellungszeit: 30. Dez 2025 um 10:54
-- Server-Version: 8.0.44
-- PHP-Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `projecty`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `charakter`
--

CREATE TABLE `charakter` (
  `id` int NOT NULL,
  `spieler_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `level` int DEFAULT '1',
  `leben` int DEFAULT '100',
  `angriff` int DEFAULT '10',
  `verteidigung` int DEFAULT '5',
  `bild` varchar(255) NOT NULL DEFAULT '/assets/image.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `spiele`
--

CREATE TABLE `spiele` (
  `id` int NOT NULL,
  `spieler_id` int NOT NULL,
  `charakter_id` int NOT NULL,
  `aktuelle_runde` int DEFAULT '0',
  `punkte` int DEFAULT '0',
  `schwierigkeit` int DEFAULT '1',
  `gespeichert_am` timestamp NULL DEFAULT NULL,
  `gegner_status` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `spieler`
--

CREATE TABLE `spieler` (
  `id` int NOT NULL,
  `benutzername` varchar(50) NOT NULL,
  `passwort` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `aktualisiert_am` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indizes für die Tabelle `charakter`
--
ALTER TABLE `charakter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spieler_id` (`spieler_id`);

--
-- Indizes für die Tabelle `spiele`
--
ALTER TABLE `spiele`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spieler_id` (`spieler_id`),
  ADD KEY `charakter_id` (`charakter_id`);

--
-- Indizes für die Tabelle `spieler`
--
ALTER TABLE `spieler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `benutzername` (`benutzername`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `charakter`
--
ALTER TABLE `charakter`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT für Tabelle `spiele`
--
ALTER TABLE `spiele`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT für Tabelle `spieler`
--
ALTER TABLE `spieler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `charakter`
--
ALTER TABLE `charakter`
  ADD CONSTRAINT `charakter_ibfk_1` FOREIGN KEY (`spieler_id`) REFERENCES `spieler` (`id`);

--
-- Constraints der Tabelle `spiele`
--
ALTER TABLE `spiele`
  ADD CONSTRAINT `spiele_ibfk_1` FOREIGN KEY (`spieler_id`) REFERENCES `spieler` (`id`),
  ADD CONSTRAINT `spiele_ibfk_2` FOREIGN KEY (`charakter_id`) REFERENCES `charakter` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
