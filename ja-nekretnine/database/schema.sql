-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: (kreiraj svoju i uvezi ovaj fajl u nju)
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dostupnost`
--

CREATE TABLE `dostupnost` (
  `id` int(11) NOT NULL,
  `nekretnina_id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `status` enum('slobodno','zauzeto') NOT NULL DEFAULT 'zauzeto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nekretnine`
--

CREATE TABLE `nekretnine` (
  `id` int(11) NOT NULL,
  `naziv` varchar(150) NOT NULL,
  `opis` text DEFAULT NULL,
  `tip` enum('Prodaja','Iznajmljivanje') NOT NULL,
  `status_prodaje` enum('Slobodno','Rezervisano','Prodato') NOT NULL DEFAULT 'Slobodno',
  `kategorija` enum('Stan','Kuca','Poslovni prostor') NOT NULL DEFAULT 'Stan',
  `zgrada_id` int(11) DEFAULT NULL,
  `cijena` varchar(50) NOT NULL,
  `lokacija` varchar(150) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `video_url` varchar(300) DEFAULT NULL,
  `dokument` varchar(255) DEFAULT NULL,
  `kvadratura` decimal(6,2) DEFAULT NULL,
  `sobe` int(11) DEFAULT 0,
  `istaknuta` tinyint(1) DEFAULT 0,
  `naslovna_slika` varchar(255) DEFAULT NULL,
  `aktivna` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slike_nekretnina`
--

CREATE TABLE `slike_nekretnina` (
  `id` int(11) NOT NULL,
  `nekretnina_id` int(11) NOT NULL,
  `fajl` varchar(255) NOT NULL,
  `tip` enum('slika','video') NOT NULL DEFAULT 'slika',
  `redosled` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slike_zgrada`
--

CREATE TABLE `slike_zgrada` (
  `id` int(11) NOT NULL,
  `zgrada_id` int(11) NOT NULL,
  `fajl` varchar(255) NOT NULL,
  `tip` enum('slika','video') NOT NULL DEFAULT 'slika',
  `redosled` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `upiti_nekretnina`
--

CREATE TABLE `upiti_nekretnina` (
  `id` int(11) NOT NULL,
  `nekretnina_id` int(11) DEFAULT NULL,
  `zgrada_id` int(11) DEFAULT NULL,
  `ime` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefon` varchar(30) DEFAULT NULL,
  `poruka` text DEFAULT NULL,
  `procitano` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `odgovor` text DEFAULT NULL,
  `odgovoreno_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zgrade`
--

CREATE TABLE `zgrade` (
  `id` int(11) NOT NULL,
  `naziv` varchar(150) NOT NULL,
  `opis` text DEFAULT NULL,
  `lokacija` varchar(150) DEFAULT NULL,
  `adresa` varchar(200) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `godina_izgradnje` int(11) DEFAULT NULL,
  `broj_spratova` int(11) DEFAULT NULL,
  `video_url` varchar(300) DEFAULT NULL,
  `dokument` varchar(255) DEFAULT NULL,
  `naslovna_slika` varchar(255) DEFAULT NULL,
  `istaknuta` tinyint(1) DEFAULT 0,
  `aktivna` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `dostupnost`
--
ALTER TABLE `dostupnost`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_datum` (`nekretnina_id`,`datum`);

--
-- Indexes for table `nekretnine`
--
ALTER TABLE `nekretnine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zgrada_id` (`zgrada_id`);

--
-- Indexes for table `slike_nekretnina`
--
ALTER TABLE `slike_nekretnina`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nekretnina_id` (`nekretnina_id`);

--
-- Indexes for table `slike_zgrada`
--
ALTER TABLE `slike_zgrada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zgrada_id` (`zgrada_id`);

--
-- Indexes for table `upiti_nekretnina`
--
ALTER TABLE `upiti_nekretnina`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nekretnina_id` (`nekretnina_id`),
  ADD KEY `zgrada_id` (`zgrada_id`);

--
-- Indexes for table `zgrade`
--
ALTER TABLE `zgrade`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dostupnost`
--
ALTER TABLE `dostupnost`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nekretnine`
--
ALTER TABLE `nekretnine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slike_nekretnina`
--
ALTER TABLE `slike_nekretnina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slike_zgrada`
--
ALTER TABLE `slike_zgrada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `upiti_nekretnina`
--
ALTER TABLE `upiti_nekretnina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zgrade`
--
ALTER TABLE `zgrade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dostupnost`
--
ALTER TABLE `dostupnost`
  ADD CONSTRAINT `dostupnost_ibfk_1` FOREIGN KEY (`nekretnina_id`) REFERENCES `nekretnine` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nekretnine`
--
ALTER TABLE `nekretnine`
  ADD CONSTRAINT `nekretnine_ibfk_1` FOREIGN KEY (`zgrada_id`) REFERENCES `zgrade` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `slike_nekretnina`
--
ALTER TABLE `slike_nekretnina`
  ADD CONSTRAINT `slike_nekretnina_ibfk_1` FOREIGN KEY (`nekretnina_id`) REFERENCES `nekretnine` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `slike_zgrada`
--
ALTER TABLE `slike_zgrada`
  ADD CONSTRAINT `slike_zgrada_ibfk_1` FOREIGN KEY (`zgrada_id`) REFERENCES `zgrade` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `upiti_nekretnina`
--
ALTER TABLE `upiti_nekretnina`
  ADD CONSTRAINT `upiti_nekretnina_ibfk_1` FOREIGN KEY (`nekretnina_id`) REFERENCES `nekretnine` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `upiti_nekretnina_ibfk_2` FOREIGN KEY (`zgrada_id`) REFERENCES `zgrade` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
