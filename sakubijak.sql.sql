-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 10, 2026 at 10:42 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sakubijak`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED NOT NULL,
  `batas_anggaran` bigint UNSIGNED NOT NULL,
  `jenis_anggaran` enum('fixed','flexible') NOT NULL DEFAULT 'flexible',
  `bulan` tinyint UNSIGNED NOT NULL,
  `tahun` smallint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `user_id`, `category_id`, `batas_anggaran`, `jenis_anggaran`, `bulan`, `tahun`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 200000, 'flexible', 10, 2026, '2026-10-27 18:08:22', '2026-10-27 18:08:22'),
(3, 1, 6, 1000000, 'flexible', 10, 2026, '2026-10-27 18:08:37', '2026-10-27 18:08:37'),
(4, 1, 3, 500000, 'flexible', 10, 2026, '2026-10-27 18:24:35', '2026-10-27 18:24:35'),
(5, 1, 3, 500000, 'fixed', 11, 2026, '2026-11-04 10:11:57', '2026-11-04 10:11:57'),
(7, 1, 1, 300000, 'flexible', 11, 2026, '2026-11-04 21:25:28', '2026-11-04 21:25:28');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(50) NOT NULL,
  `tipe` enum('pemasukan','pengeluaran','tabungan') NOT NULL,
  `ikon` varchar(30) NOT NULL DEFAULT 'dots',
  `warna` varchar(7) NOT NULL DEFAULT '#8D9F94',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nama`, `tipe`, `ikon`, `warna`, `created_at`, `updated_at`) VALUES
(1, 'Makan', 'pengeluaran', 'utensils', '#344B3D', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(2, 'Transportasi', 'pengeluaran', 'bus', '#4F7A5E', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(3, 'Kos', 'pengeluaran', 'home', '#7FA88C', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(4, 'Tugas/Skripsi', 'pengeluaran', 'book', '#A9C5B0', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(5, 'Hiburan', 'pengeluaran', 'film', '#C9A66B', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(6, 'Belanja', 'pengeluaran', 'bag', '#8D9F94', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(7, 'Kesehatan', 'pengeluaran', 'heart', '#6B8F8A', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(8, 'Lainnya', 'pengeluaran', 'dots', '#B7C4A3', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(9, 'Uang Saku', 'pemasukan', 'wallet', '#3D8B62', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(10, 'Beasiswa', 'pemasukan', 'cap', '#3D8B62', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(11, 'Gaji', 'pemasukan', 'briefcase', '#3D8B62', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(12, 'Lainnya', 'pemasukan', 'dots', '#3D8B62', '2026-10-27 17:58:21', '2026-10-27 17:58:21'),
(13, 'Tabungan', 'tabungan', 'coins', '#344B3D', '2026-10-27 17:58:21', '2026-10-27 17:58:21');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `reminder_type` enum('daily_expense','bill_payment','budget_limit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date DEFAULT NULL,
  `reminder_time` time NOT NULL DEFAULT '20:00:00',
  `amount` bigint UNSIGNED DEFAULT NULL,
  `threshold_percent` tinyint UNSIGNED NOT NULL DEFAULT '80',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings`
--

CREATE TABLE `savings` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `nama_target` varchar(100) NOT NULL,
  `target_nominal` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED NOT NULL,
  `savings_id` int UNSIGNED DEFAULT NULL,
  `tipe` enum('pemasukan','pengeluaran','tabungan') NOT NULL,
  `nominal` bigint UNSIGNED NOT NULL,
  `deskripsi` varchar(120) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `category_id`, `savings_id`, `tipe`, `nominal`, `deskripsi`, `tanggal`, `created_at`, `updated_at`) VALUES
(1, 1, 9, NULL, 'pemasukan', 2000000, 'Dari Papa', '2026-10-01', '2026-10-27 18:07:06', '2026-10-27 18:07:06'),
(2, 1, 9, NULL, 'pemasukan', 300000, 'Dari Kakak', '2026-10-27', '2026-10-27 18:07:31', '2026-10-27 18:07:31'),
(3, 1, 6, NULL, 'pengeluaran', 700000, 'Sepatu', '2026-10-14', '2026-10-27 18:09:08', '2026-10-27 18:09:08'),
(4, 1, 2, NULL, 'pengeluaran', 200000, 'Bensin', '2026-10-15', '2026-10-27 18:09:30', '2026-10-27 18:09:30'),
(5, 1, 3, NULL, 'pengeluaran', 50000, 'Bayar Sewa Kost', '2026-10-25', '2026-10-27 18:09:59', '2026-10-27 18:39:58'),
(6, 1, 9, NULL, 'pemasukan', 800000, 'Dari kakak 😍', '2026-11-04', '2026-11-04 10:11:30', '2026-11-04 10:11:30'),
(7, 1, 3, NULL, 'pengeluaran', 500000, 'Bayar Kost', '2026-11-04', '2026-11-04 10:32:07', '2026-11-04 10:32:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Gratia', 'gratiatelaumbanua7@gmail.com', '$2y$10$Go3Y1KQWHiyyp4unZWoLiOvVIC1LNCkktYGFqHUeZGh3Jiu3Jo0cq', '2026-10-27 18:01:25', '2026-10-27 18:01:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_budget` (`user_id`,`category_id`,`bulan`,`tahun`),
  ADD KEY `fk_budget_category` (`category_id`),
  ADD KEY `idx_budget_period` (`user_id`,`tahun`,`bulan`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category` (`nama`,`tipe`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reminders_user_active` (`user_id`,`is_active`,`reminder_type`);

--
-- Indexes for table `savings`
--
ALTER TABLE `savings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_savings_user` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tx_category` (`category_id`),
  ADD KEY `fk_tx_savings` (`savings_id`),
  ADD KEY `idx_tx_user_date` (`user_id`,`tanggal`),
  ADD KEY `idx_tx_user_tipe` (`user_id`,`tipe`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budget_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `fk_reminders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings`
--
ALTER TABLE `savings`
  ADD CONSTRAINT `fk_savings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_tx_savings` FOREIGN KEY (`savings_id`) REFERENCES `savings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
