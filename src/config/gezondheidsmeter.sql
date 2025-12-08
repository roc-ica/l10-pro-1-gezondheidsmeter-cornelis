-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Dec 08, 2025 at 08:37 AM
-- Server version: 10.11.15-MariaDB-ubu2204
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gezondheidsmeter`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` varchar(100) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`id`, `admin_user_id`, `action_type`, `target_table`, `target_id`, `details`, `created_at`) VALUES
(1, 1, 'view', NULL, NULL, '{\"page\":\"analytics\"}', '2025-12-04 08:47:53'),
(2, 1, 'view', NULL, NULL, '{\"page\":\"analytics\"}', '2025-12-04 08:47:58'),
(3, 1, 'view', NULL, NULL, '{\"page\":\"analytics\"}', '2025-12-04 08:48:03'),
(4, 1, 'view', NULL, NULL, '{\"page\":\"analytics\"}', '2025-12-04 08:48:41'),
(5, 1, 'view', NULL, NULL, '{\"page\":\"analytics\"}', '2025-12-04 08:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `answer_text` text DEFAULT NULL,
  `score` tinyint(3) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `entry_id`, `question_id`, `answer_text`, `score`, `created_at`) VALUES
(7, 2, 1, 'Nee / Laag', NULL, '2025-12-04 09:06:55'),
(8, 2, 2, 'Een beetje', NULL, '2025-12-04 09:06:56'),
(9, 2, 3, 'Neutraal', NULL, '2025-12-04 09:06:57'),
(10, 2, 4, 'Nee / Laag', NULL, '2025-12-04 09:06:59'),
(11, 2, 5, 'Neutraal', NULL, '2025-12-04 09:07:00'),
(12, 2, 6, 'Meerdere keren', NULL, '2025-12-04 09:07:01');

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

CREATE TABLE `challenges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_entries`
--

CREATE TABLE `daily_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `entry_date` date NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_entries`
--

INSERT INTO `daily_entries` (`id`, `user_id`, `entry_date`, `submitted_at`, `notes`) VALUES
(2, 3, '2025-12-04', '2025-12-04 09:07:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `device_identifier` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `connected_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_metrics`
--

CREATE TABLE `device_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `device_id` bigint(20) UNSIGNED NOT NULL,
  `metric_type` varchar(50) NOT NULL,
  `metric_value` float NOT NULL,
  `captured_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `global_statistics`
--

CREATE TABLE `global_statistics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stats` longtext DEFAULT NULL CHECK (json_valid(`stats`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_analysis`
--

CREATE TABLE `monthly_analysis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `month_start` date NOT NULL,
  `month_end` date NOT NULL,
  `summary` longtext DEFAULT NULL CHECK (json_valid(`summary`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `notif_type` varchar(80) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `is_sent` tinyint(1) DEFAULT 0,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pillars`
--

CREATE TABLE `pillars` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pillars`
--

INSERT INTO `pillars` (`id`, `name`, `description`, `color`) VALUES
(1, 'Voeding', 'Voedingskeuzes en dranken.', '#FF6B6B'),
(2, 'Beweging', 'Dagelijkse beweging en vervoerskeuzes.', '#4ECDC4'),
(3, 'Slaap', 'Slaaproutine en nachtrust.', '#556270'),
(4, 'Verslavingen', 'Alcohol, roken, drugs.', '#C7F464'),
(5, 'Sociaal', 'Sociale interactie & participatie.', '#FFA500'),
(6, 'Mentaal', 'Stress, ontspanning, schermtijd.', '#C44DFF');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pillar_id` tinyint(3) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `input_type` enum('choice','number','boolean','text') NOT NULL DEFAULT 'choice',
  `choices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`choices`)),
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `pillar_id`, `question_text`, `input_type`, `choices`, `active`, `created_at`) VALUES
(1, 1, 'Hoeveel glazen water heb je vandaag gedronken?', 'number', NULL, 1, '2025-11-17 10:34:03'),
(2, 1, 'Heb je frisdrank of alcohol gedronken vandaag?', 'choice', '[\"Nee\",\"Een beetje\",\"Veel\"]', 1, '2025-11-17 10:34:03'),
(3, 2, 'Hoeveel minuten heb je vandaag bewogen?', 'number', NULL, 1, '2025-11-17 10:34:03'),
(4, 3, 'Hoeveel uur heb je geslapen?', 'number', NULL, 1, '2025-11-17 10:34:03'),
(5, 6, 'Heb je meer dan 2 uur extra schermtijd gehad vandaag?', 'boolean', NULL, 1, '2025-11-17 10:34:03'),
(6, 4, 'Heb je alcohol of wiet gebruikt vandaag?', 'choice', '[\"Nee\",\"1 keer\",\"Meerdere keren\"]', 1, '2025-11-17 10:34:03');

-- --------------------------------------------------------

--
-- Table structure for table `resets`
--

CREATE TABLE `resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `performed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reset_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `scope` enum('user','all') DEFAULT 'user',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `reset_type` enum('daily_entries','answers','full') DEFAULT 'full'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `display_name` varchar(150) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `block_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `is_admin`, `display_name`, `birthdate`, `gender`, `created_at`, `last_login`, `is_active`, `block_reason`) VALUES
(1, 'Hoi', 'admin@example.com', '$2y$10$ZRRZN1Yf4Z0EhT58n3By/ezt/dHF4cJukvHSlQ59E7FouZCgbUQ3i', 1, NULL, NULL, NULL, '2025-11-26 08:15:55', '2025-12-04 08:47:41', 1, NULL),
(2, 'testuser123', 'testuser123@example.com', '$2y$10$WOlRqRBqz/CShsxWeoy8SuX3tkXTAfvU.WBU0ImdaAcL0AisSHoJe', 0, NULL, NULL, NULL, '2025-11-28 11:30:54', '2025-12-04 09:06:34', 1, NULL),
(3, 'test', 'test@email.com', '$2y$10$WyN2vQdQSPQs.Y6QcldlIucaot9T4BSZmSgw4Xa5P0KbHpI5qJ70u', 0, NULL, NULL, NULL, '2025-12-01 08:48:44', '2025-12-08 08:34:49', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_challenges`
--

CREATE TABLE `user_challenges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `challenge_id` bigint(20) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`progress`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_meta_admin_view`
--

CREATE TABLE `user_meta_admin_view` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `display_id` char(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_analysis`
--

CREATE TABLE `weekly_analysis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_user_id` (`admin_user_id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `entry_id` (`entry_id`,`question_id`);

--
-- Indexes for table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_entries`
--
ALTER TABLE `daily_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`entry_date`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `device_metrics`
--
ALTER TABLE `device_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `global_statistics`
--
ALTER TABLE `global_statistics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_analysis`
--
ALTER TABLE `monthly_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_month` (`user_id`,`month_start`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pillars`
--
ALTER TABLE `pillars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pillar_id` (`pillar_id`);

--
-- Indexes for table `resets`
--
ALTER TABLE `resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_challenges`
--
ALTER TABLE `user_challenges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `user_meta_admin_view`
--
ALTER TABLE `user_meta_admin_view`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `weekly_analysis`
--
ALTER TABLE `weekly_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`week_start`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_entries`
--
ALTER TABLE `daily_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_metrics`
--
ALTER TABLE `device_metrics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `global_statistics`
--
ALTER TABLE `global_statistics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_analysis`
--
ALTER TABLE `monthly_analysis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pillars`
--
ALTER TABLE `pillars`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `resets`
--
ALTER TABLE `resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_challenges`
--
ALTER TABLE `user_challenges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weekly_analysis`
--
ALTER TABLE `weekly_analysis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `daily_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_entries`
--
ALTER TABLE `daily_entries`
  ADD CONSTRAINT `daily_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `device_metrics`
--
ALTER TABLE `device_metrics`
  ADD CONSTRAINT `device_metrics_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_analysis`
--
ALTER TABLE `monthly_analysis`
  ADD CONSTRAINT `monthly_analysis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`pillar_id`) REFERENCES `pillars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resets`
--
ALTER TABLE `resets`
  ADD CONSTRAINT `resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resets_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_challenges`
--
ALTER TABLE `user_challenges`
  ADD CONSTRAINT `user_challenges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_challenges_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_meta_admin_view`
--
ALTER TABLE `user_meta_admin_view`
  ADD CONSTRAINT `user_meta_admin_view_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_analysis`
--
ALTER TABLE `weekly_analysis`
  ADD CONSTRAINT `weekly_analysis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
