-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 15 jan. 2025 à 23:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `exammaster`
--

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'dd201', 'dd201', '2025-01-05 04:20:06'),
(2, 'dd202', 'dd202', '2025-01-05 04:20:19');

-- --------------------------------------------------------

--
-- Structure de la table `class_student`
--

CREATE TABLE `class_student` (
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `class_student`
--

INSERT INTO `class_student` (`class_id`, `student_id`) VALUES
(1, 4),
(1, 13),
(1, 16),
(2, 12),
(2, 17);

-- --------------------------------------------------------

--
-- Structure de la table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `exam_date` datetime DEFAULT current_timestamp(),
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('draft','published','completed') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `exams`
--

INSERT INTO `exams` (`id`, `title`, `subject_id`, `class_id`, `teacher_id`, `duration`, `total_points`, `exam_date`, `start_time`, `end_time`, `instructions`, `status`, `created_at`, `updated_at`) VALUES
(12, 'CC1 ECO', 1, 1, 2, 90, 20, '2025-01-15 00:00:00', '22:49:00', '23:02:00', 'HHHHHHHHHHHHHHHHHHH', 'published', '2025-01-15 21:49:34', '2025-01-15 21:49:34'),
(13, 'tets', 1, 1, 2, 90, 20, '2025-01-15 00:00:00', '22:55:00', '23:56:00', 'HHHHHHHHHHHHHHHHHHHH', 'published', '2025-01-15 21:56:35', '2025-01-15 21:56:35'),
(14, 'QHHHHHH', 1, 1, 2, 90, 10, '2025-01-15 00:00:00', '13:12:00', '14:12:00', 'HHHHHHHHHHHHH', 'published', '2025-01-15 22:12:38', '2025-01-15 22:12:38');

-- --------------------------------------------------------

--
-- Structure de la table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `status` enum('passed','failed') NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') NOT NULL DEFAULT 'info',
  `priority` enum('basse','moyenne','haute') NOT NULL DEFAULT 'moyenne',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `priority`, `link`, `is_read`, `created_at`) VALUES
(5, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-11 20:38:32'),
(6, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-11 20:43:52'),
(7, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-11 20:44:34'),
(8, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-11 20:48:36'),
(9, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-11 20:48:38'),
(10, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-13 22:36:06'),
(11, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-15 06:39:22'),
(12, 1, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success', 'moyenne', NULL, 0, '2025-01-15 09:29:00');

-- --------------------------------------------------------

--
-- Structure de la table `notification_categories`
--

CREATE TABLE `notification_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notification_categories`
--

INSERT INTO `notification_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'System', 'System and maintenance notifications', '2025-01-05 03:27:30'),
(2, 'Exams', 'Exam-related notifications', '2025-01-05 03:27:30'),
(3, 'Accounts', 'Account management notifications', '2025-01-05 03:27:30'),
(4, 'Results', 'Exam results notifications', '2025-01-05 03:27:30');

-- --------------------------------------------------------

--
-- Structure de la table `notification_categorization`
--

CREATE TABLE `notification_categorization` (
  `notification_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `user_id` int(11) NOT NULL,
  `notification_type` enum('info','warning','success','error') NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `interface_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('qcm','text','truefalse') NOT NULL,
  `points` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_type`, `points`, `created_at`) VALUES
(0, 7, 'c\'est quoi git ', 'text', 10, '2025-01-15 15:13:52'),
(1, 1, 'Question 1 test', 'qcm', 10, '2025-01-05 04:48:38'),
(2, 1, 'Question 2 test ', 'text', 10, '2025-01-05 04:48:38'),
(3, 2, 'Test', 'text', 20, '2025-01-05 05:09:24'),
(4, 3, 'Q1', 'text', 5, '2025-01-06 09:41:02'),
(5, 3, 'Q2', 'qcm', 5, '2025-01-06 09:41:02'),
(6, 3, 'Q3', 'truefalse', 5, '2025-01-06 09:41:02'),
(7, 4, 'QUESTION 1', 'qcm', 12, '2025-01-07 17:02:14'),
(8, 5, 'RCA OU WAC', 'qcm', 2, '2025-01-13 16:00:38'),
(9, 6, 'Rca ou wac', 'text', 3, '2025-01-14 00:10:44');

-- --------------------------------------------------------

--
-- Structure de la table `question_answers`
--

CREATE TABLE `question_answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `correct_answer` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `question_options`
--

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `question_options`
--

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`) VALUES
(1, 1, 'Choix 1', 1, '2025-01-05 04:48:38'),
(2, 1, 'Choix 1', 0, '2025-01-05 04:48:38'),
(3, 5, 'op1', 1, '2025-01-06 09:41:02'),
(4, 5, 'op2', 1, '2025-01-06 09:41:02'),
(5, 5, 'op3', 0, '2025-01-06 09:41:02'),
(6, 7, 'CHOIX1', 1, '2025-01-07 17:02:14'),
(7, 7, 'CHOIX2', 0, '2025-01-07 17:02:14');

-- --------------------------------------------------------

--
-- Structure de la table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `exam_result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Préparation d’un projet web', 'Préparation d’un projet web', '2025-01-05 04:21:58'),
(2, 'Approche agile', 'Approche agile', '2025-01-05 04:21:58'),
(3, 'Gestion des données', 'Gestion des données', '2025-01-05 04:21:58'),
(4, 'Développement front-end', 'Développement front-end', '2025-01-05 04:21:58'),
(5, 'Développement back-end', 'Développement back-end', '2025-01-05 04:21:58');

-- --------------------------------------------------------

--
-- Structure de la table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `teachers`
--

INSERT INTO `teachers` (`id`, `full_name`) VALUES
(1, 'Unknown Teacher'),
(2, 'John Doe'),
(3, 'Jane Smith');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrateur','Enseignant','Etudiant') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `role`, `created_at`, `updated_at`, `deleted_at`, `status`) VALUES
(1, 'admin', 'admin', 'admin@email.com', 'admin', 'Administrateur', '2025-01-05 03:46:15', '2025-01-10 09:50:54', '2025-01-10 09:50:54', NULL),
(2, 'enseignant', 'enseignant', 'enseignant@email.com', 'enseignant', 'Enseignant', '2025-01-05 03:47:06', '2025-01-07 16:57:03', NULL, NULL),
(4, 'achraf', 'ghazal', 'achraf@email.com', 'achraf', 'Etudiant', '2025-01-05 04:23:10', '2025-01-15 07:44:26', NULL, 'approved');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `class_student`
--
ALTER TABLE `class_student`
  ADD PRIMARY KEY (`class_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Index pour la table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Index pour la table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_user` (`user_id`,`is_read`),
  ADD KEY `idx_notification_date` (`created_at`);

--
-- Index pour la table `notification_categories`
--
ALTER TABLE `notification_categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notification_categorization`
--
ALTER TABLE `notification_categorization`
  ADD PRIMARY KEY (`notification_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Index pour la table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`user_id`,`notification_type`);

--
-- Index pour la table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Index pour la table `question_answers`
--
ALTER TABLE `question_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Index pour la table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Index pour la table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_result_id` (`exam_result_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Index pour la table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `fk_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
