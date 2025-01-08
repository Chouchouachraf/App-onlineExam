-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: localhost    Database: exammaster
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `class_student`
--
CREATE DATABASE exammaster;

USE exammaster;

DROP TABLE IF EXISTS `class_student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_student` (
  `class_id` int NOT NULL,
  `student_id` int NOT NULL,
  PRIMARY KEY (`class_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `class_student_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_student_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_student`
--

LOCK TABLES `class_student` WRITE;
/*!40000 ALTER TABLE `class_student` DISABLE KEYS */;
INSERT INTO `class_student` VALUES (1,4);
/*!40000 ALTER TABLE `class_student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'dd201','dd201','2025-01-05 05:20:06'),(2,'dd202','dd202','2025-01-05 05:20:19');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `status` enum('passed','failed') NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_results`
--

LOCK TABLES `exam_results` WRITE;
/*!40000 ALTER TABLE `exam_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject_id` int NOT NULL,
  `class_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `duration` int NOT NULL,
  `total_points` int DEFAULT '0',
  `exam_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `instructions` text,
  `status` enum('draft','published','completed') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `class_id` (`class_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `exams_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (1,'Laravel (Test)',5,1,2,60,20,'2025-01-04 00:00:00','22:00:00','23:00:00','Instructions test','published','2025-01-05 05:48:38','2025-01-05 06:06:51'),(2,'dsfsdf',5,1,2,34,20,'2025-01-04 00:00:00','22:08:00','22:13:00','Test','published','2025-01-05 06:09:24','2025-01-05 06:09:24');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_categories`
--

DROP TABLE IF EXISTS `notification_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_categories`
--

LOCK TABLES `notification_categories` WRITE;
/*!40000 ALTER TABLE `notification_categories` DISABLE KEYS */;
INSERT INTO `notification_categories` VALUES (1,'System','System and maintenance notifications','2025-01-05 04:27:30'),(2,'Exams','Exam-related notifications','2025-01-05 04:27:30'),(3,'Accounts','Account management notifications','2025-01-05 04:27:30'),(4,'Results','Exam results notifications','2025-01-05 04:27:30');
/*!40000 ALTER TABLE `notification_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_categorization`
--

DROP TABLE IF EXISTS `notification_categorization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_categorization` (
  `notification_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`notification_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `notification_categorization_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_categorization_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `notification_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_categorization`
--

LOCK TABLES `notification_categorization` WRITE;
/*!40000 ALTER TABLE `notification_categorization` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_categorization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `user_id` int NOT NULL,
  `notification_type` enum('info','warning','success','error') NOT NULL,
  `email_enabled` tinyint(1) DEFAULT '1',
  `interface_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user_id`,`notification_type`),
  CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') NOT NULL DEFAULT 'info',
  `priority` enum('basse','moyenne','haute') NOT NULL DEFAULT 'moyenne',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`,`is_read`),
  KEY `idx_notification_date` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_answers`
--

DROP TABLE IF EXISTS `question_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `correct_answer` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `question_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_answers`
--

LOCK TABLES `question_answers` WRITE;
/*!40000 ALTER TABLE `question_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_options`
--

DROP TABLE IF EXISTS `question_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_options`
--

LOCK TABLES `question_options` WRITE;
/*!40000 ALTER TABLE `question_options` DISABLE KEYS */;
INSERT INTO `question_options` VALUES (1,1,'Choix 1',1,'2025-01-05 05:48:38'),(2,1,'Choix 1',0,'2025-01-05 05:48:38');
/*!40000 ALTER TABLE `question_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('qcm','text','truefalse') NOT NULL,
  `points` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (1,1,'Question 1 test','qcm',10,'2025-01-05 05:48:38'),(2,1,'Question 2 test ','text',10,'2025-01-05 05:48:38'),(3,2,'Test','text',20,'2025-01-05 06:09:24');
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_answers`
--

DROP TABLE IF EXISTS `student_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_result_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer_text` text,
  `points_earned` decimal(5,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_result_id` (`exam_result_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`exam_result_id`) REFERENCES `exam_results` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
) ENGINE=InnoDB ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_answers`
--

LOCK TABLES `student_answers` WRITE;
/*!40000 ALTER TABLE `student_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Préparation d’un projet web','Préparation d’un projet web','2025-01-05 05:21:58'),(2,'Approche agile','Approche agile','2025-01-05 05:21:58'),(3,'Gestion des données','Gestion des données','2025-01-05 05:21:58'),(4,'Développement front-end','Développement front-end','2025-01-05 05:21:58'),(5,'Développement back-end','Développement back-end','2025-01-05 05:21:58');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrateur','Enseignant','Etudiant') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin','admin@email.com','admin','Administrateur','2025-01-05 04:46:15','2025-01-05 04:46:15'),(2,'enseignant','enseignant','enseignant@email.com','enseignant','Enseignant','2025-01-05 04:47:06','2025-01-05 04:47:06'),(4,'achraf','ghazal','achraf@email.com','achraf','Etudiant','2025-01-05 05:23:10','2025-01-05 05:23:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-01-04 22:21:33
