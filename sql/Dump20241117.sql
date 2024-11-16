-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: localhost    Database: freelancerwebsite
-- ------------------------------------------------------
-- Server version	8.0.39

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
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,1,'Create Category','Added category: Digital Marketing','2024-11-16 18:18:53'),(2,1,'Delete User','Deactivated user ID: 4','2024-11-16 18:18:53'),(3,1,'Create Category','Added category: Video Editing','2024-11-12 08:30:00'),(4,1,'Update User Status','Deactivated user ID: 6','2024-11-13 05:00:00'),(5,2,'Add Gig','Freelancer ID: 4 added a gig for Video Editing','2024-11-14 09:30:00'),(6,2,'Delete Job Request','Deleted job request ID: 9','2024-11-15 06:15:00');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_category_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Web Development','2024-11-16 18:18:53'),(2,'Graphic Design','2024-11-16 18:18:53'),(3,'Content Writing','2024-11-16 18:18:53'),(4,'Digital Marketing','2024-11-16 18:18:53'),(5,'Mobile App Development','2024-11-16 18:36:36'),(6,'SEO Services','2024-11-16 18:36:36'),(7,'Video Editing','2024-11-16 18:36:36'),(8,'Social Media Management','2024-11-16 18:36:36');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gigs`
--

DROP TABLE IF EXISTS `gigs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gigs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `freelancer_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `category_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `delivery_time` int NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `freelancer_id` (`freelancer_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_gig_status` (`status`),
  CONSTRAINT `gigs_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gigs_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gigs_chk_1` CHECK ((`price` > 0)),
  CONSTRAINT `gigs_chk_2` CHECK ((`delivery_time` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gigs`
--

LOCK TABLES `gigs` WRITE;
/*!40000 ALTER TABLE `gigs` DISABLE KEYS */;
INSERT INTO `gigs` VALUES (1,2,'Build a Responsive Website','I will create a professional, responsive website using the latest technologies.',1,500.00,7,NULL,'active','2024-11-16 18:18:53','2024-11-16 18:18:53'),(2,3,'Design a Logo','Professional logo design for your brand.',2,100.00,3,NULL,'active','2024-11-16 18:18:53','2024-11-16 18:18:53'),(3,2,'Develop a Mobile App','Custom Android and iOS app development with user-friendly features.',5,1000.00,30,NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(4,3,'SEO Optimization','Improve your website ranking on search engines.',6,300.00,10,NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(5,4,'Edit Your Videos Professionally','High-quality video editing for your projects.',7,200.00,5,NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(6,5,'Manage Your Social Media','Full social media management for increased engagement.',8,400.00,7,NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36');
/*!40000 ALTER TABLE `gigs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_requests`
--

DROP TABLE IF EXISTS `job_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `freelancer_id` int NOT NULL,
  `gig_id` int NOT NULL,
  `status` enum('pending','accepted','completed','cancelled') DEFAULT 'pending',
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completion_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `freelancer_id` (`freelancer_id`),
  KEY `gig_id` (`gig_id`),
  KEY `idx_request_status` (`status`),
  CONSTRAINT `job_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_requests_ibfk_2` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_requests_ibfk_3` FOREIGN KEY (`gig_id`) REFERENCES `gigs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_requests`
--

LOCK TABLES `job_requests` WRITE;
/*!40000 ALTER TABLE `job_requests` DISABLE KEYS */;
INSERT INTO `job_requests` VALUES (1,4,2,1,'pending','2024-11-16 18:18:53',NULL),(2,5,3,2,'completed','2024-11-16 18:18:53',NULL),(3,6,2,3,'completed','2024-11-01 04:30:00','2024-11-10 12:30:00'),(4,7,3,2,'pending','2024-11-16 18:36:36',NULL),(5,8,4,4,'accepted','2024-11-16 18:36:36',NULL),(6,9,5,5,'cancelled','2024-11-05 06:30:00',NULL);
/*!40000 ALTER TABLE `job_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `freelancer_id` int NOT NULL,
  `gig_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `freelancer_id` (`freelancer_id`),
  KEY `gig_id` (`gig_id`),
  KEY `idx_review_rating` (`rating`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`gig_id`) REFERENCES `gigs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,4,2,1,5,'Great work! Highly recommend.','2024-11-16 18:18:53'),(2,5,3,2,4,'Good logo design, but took slightly longer than expected.','2024-11-16 18:18:53'),(3,4,3,2,5,'Fantastic SEO work, I saw great results in just two weeks!','2024-11-16 18:36:36'),(4,6,2,3,4,'Good app design, but a bit delayed delivery.','2024-11-16 18:36:36'),(5,8,4,4,5,'Great video editing skills! Exceeded my expectations.','2024-11-16 18:36:36'),(6,9,5,5,3,'Average work, could have been better.','2024-11-16 18:36:36');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','freelancer','client') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `idx_user_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','123','admin','Admin','User','admin@example.com',NULL,'active','2024-11-16 18:18:52','2024-11-16 18:18:52'),(2,'free1','123','freelancer','John','Doe','john.doe@example.com',NULL,'active','2024-11-16 18:18:53','2024-11-16 18:18:53'),(3,'free2','456','freelancer','Jane','Smith','jane.smith@example.com',NULL,'active','2024-11-16 18:18:53','2024-11-16 18:18:53'),(4,'cl1','123','client','Michael','Johnson','michael.johnson@example.com',NULL,'active','2024-11-16 18:18:53','2024-11-16 18:18:53'),(5,'cl2','456','client','Emily','Davis','emily.davis@example.com',NULL,'active','2024-11-16 18:18:53','2024-11-16 18:18:53'),(6,'admin2','456','admin','Alice','Brown','alice.brown@example.com',NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(7,'free3','789','freelancer','Robert','Taylor','robert.taylor@example.com',NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(8,'free4','321','freelancer','Sophia','Martinez','sophia.martinez@example.com',NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(9,'cl3','789','client','David','Harris','david.harris@example.com',NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36'),(10,'cl4','321','client','Emma','Clark','emma.clark@example.com',NULL,'active','2024-11-16 18:36:36','2024-11-16 18:36:36');
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

-- Dump completed on 2024-11-17  0:08:02
