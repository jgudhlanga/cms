-- MySQL dump 10.13  Distrib 9.2.0, for macos15.2 (arm64)
--
-- Host: 127.0.0.1    Database: hrepoly
-- ------------------------------------------------------
-- Server version	9.2.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_levels`
--

DROP TABLE IF EXISTS `academic_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `position` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_levels_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_levels`
--

/*!40000 ALTER TABLE `academic_levels` DISABLE KEYS */;
INSERT INTO `academic_levels` VALUES (1,'Primary school',NULL,1,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Secondary school',NULL,2,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Advanced Level',NULL,3,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'Tertiary Level',NULL,4,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `academic_levels` ENABLE KEYS */;

--
-- Table structure for table `academic_records`
--

DROP TABLE IF EXISTS `academic_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `academic_level_id` bigint unsigned NOT NULL,
  `school` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `place` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_year` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_year` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_unique_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_board` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_year` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_center` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_results` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_records_student_id_foreign` (`student_id`),
  KEY `academic_records_academic_level_id_foreign` (`academic_level_id`),
  KEY `academic_records_tenant_id_index` (`tenant_id`),
  CONSTRAINT `academic_records_academic_level_id_foreign` FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`),
  CONSTRAINT `academic_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `academic_records_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_records`
--

/*!40000 ALTER TABLE `academic_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_records` ENABLE KEYS */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=736 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'Tenant','created','App\\Models\\Tenants\\Tenant','created',1,NULL,NULL,'{\"attributes\": {\"meta\": null, \"name\": \"Harare Poly\", \"is_active\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(2,'Tenant','created','App\\Models\\Tenants\\Tenant','created',2,NULL,NULL,'{\"attributes\": {\"meta\": null, \"name\": \"Penstej Systems\", \"is_active\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(3,'AclModule','created','App\\Models\\Acl\\Module','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"accommodations\", \"title\": \"Accommodations\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(4,'AclModule','created','App\\Models\\Acl\\Module','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"acl\", \"title\": \"Acl\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(5,'AclModule','created','App\\Models\\Acl\\Module','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"communications\", \"title\": \"Communications\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(6,'AclModule','created','App\\Models\\Acl\\Module','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"dashboards\", \"title\": \"Dashboards\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(7,'AclModule','created','App\\Models\\Acl\\Module','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"enrolments\", \"title\": \"Enrolments\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(8,'AclModule','created','App\\Models\\Acl\\Module','created',6,NULL,NULL,'{\"attributes\": {\"slug\": \"examinations\", \"title\": \"Examinations\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(9,'AclModule','created','App\\Models\\Acl\\Module','created',7,NULL,NULL,'{\"attributes\": {\"slug\": \"institution\", \"title\": \"Institution\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(10,'AclModule','created','App\\Models\\Acl\\Module','created',8,NULL,NULL,'{\"attributes\": {\"slug\": \"other\", \"title\": \"Other\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(11,'AclModule','created','App\\Models\\Acl\\Module','created',9,NULL,NULL,'{\"attributes\": {\"slug\": \"reports\", \"title\": \"Reports\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(12,'AclModule','created','App\\Models\\Acl\\Module','created',10,NULL,NULL,'{\"attributes\": {\"slug\": \"root\", \"title\": \"Root\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(13,'AclModule','created','App\\Models\\Acl\\Module','created',11,NULL,NULL,'{\"attributes\": {\"slug\": \"settings\", \"title\": \"Settings\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(14,'AclModule','created','App\\Models\\Acl\\Module','created',12,NULL,NULL,'{\"attributes\": {\"slug\": \"shared\", \"title\": \"Shared\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(15,'AclModule','created','App\\Models\\Acl\\Module','created',13,NULL,NULL,'{\"attributes\": {\"slug\": \"students\", \"title\": \"Students\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(16,'AclModule','created','App\\Models\\Acl\\Module','created',14,NULL,NULL,'{\"attributes\": {\"slug\": \"tenants\", \"title\": \"Tenants\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(17,'AclModule','created','App\\Models\\Acl\\Module','created',15,NULL,NULL,'{\"attributes\": {\"slug\": \"users\", \"title\": \"Users\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(18,'Permission','created','App\\Models\\Acl\\Permission','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"view:acl-settings\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(19,'Permission','created','App\\Models\\Acl\\Permission','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(20,'Permission','created','App\\Models\\Acl\\Permission','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"view:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(21,'Permission','created','App\\Models\\Acl\\Permission','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"create:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(22,'Permission','created','App\\Models\\Acl\\Permission','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"update:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(23,'Permission','created','App\\Models\\Acl\\Permission','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"delete:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(24,'Permission','created','App\\Models\\Acl\\Permission','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"restore:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(25,'Permission','created','App\\Models\\Acl\\Permission','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(26,'Permission','created','App\\Models\\Acl\\Permission','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"import:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(27,'Permission','created','App\\Models\\Acl\\Permission','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"export:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(28,'Permission','created','App\\Models\\Acl\\Permission','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(29,'Permission','created','App\\Models\\Acl\\Permission','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(30,'Permission','created','App\\Models\\Acl\\Permission','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"view:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(31,'Permission','created','App\\Models\\Acl\\Permission','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"create:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(32,'Permission','created','App\\Models\\Acl\\Permission','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"update:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(33,'Permission','created','App\\Models\\Acl\\Permission','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"delete:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(34,'Permission','created','App\\Models\\Acl\\Permission','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"restore:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(35,'Permission','created','App\\Models\\Acl\\Permission','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(36,'Permission','created','App\\Models\\Acl\\Permission','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"import:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(37,'Permission','created','App\\Models\\Acl\\Permission','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"export:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(38,'Permission','created','App\\Models\\Acl\\Permission','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(39,'Permission','created','App\\Models\\Acl\\Permission','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(40,'Permission','created','App\\Models\\Acl\\Permission','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"view:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(41,'Permission','created','App\\Models\\Acl\\Permission','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"create:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(42,'Permission','created','App\\Models\\Acl\\Permission','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"update:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(43,'Permission','created','App\\Models\\Acl\\Permission','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"delete:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(44,'Permission','created','App\\Models\\Acl\\Permission','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"restore:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(45,'Permission','created','App\\Models\\Acl\\Permission','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(46,'Permission','created','App\\Models\\Acl\\Permission','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"import:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(47,'Permission','created','App\\Models\\Acl\\Permission','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"export:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(48,'Permission','created','App\\Models\\Acl\\Permission','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(49,'Permission','created','App\\Models\\Acl\\Permission','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(50,'Permission','created','App\\Models\\Acl\\Permission','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"view:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(51,'Permission','created','App\\Models\\Acl\\Permission','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"create:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(52,'Permission','created','App\\Models\\Acl\\Permission','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"update:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(53,'Permission','created','App\\Models\\Acl\\Permission','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"delete:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(54,'Permission','created','App\\Models\\Acl\\Permission','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"restore:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(55,'Permission','created','App\\Models\\Acl\\Permission','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(56,'Permission','created','App\\Models\\Acl\\Permission','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"import:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(57,'Permission','created','App\\Models\\Acl\\Permission','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"export:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(58,'Permission','created','App\\Models\\Acl\\Permission','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(59,'Permission','created','App\\Models\\Acl\\Permission','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(60,'Permission','created','App\\Models\\Acl\\Permission','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:dashboards\", \"module_id\": 4, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(61,'Permission','created','App\\Models\\Acl\\Permission','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"view:dashboards\", \"module_id\": 4, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(62,'Permission','created','App\\Models\\Acl\\Permission','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(63,'Permission','created','App\\Models\\Acl\\Permission','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"view:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(64,'Permission','created','App\\Models\\Acl\\Permission','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"create:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(65,'Permission','created','App\\Models\\Acl\\Permission','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"update:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(66,'Permission','created','App\\Models\\Acl\\Permission','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"delete:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(67,'Permission','created','App\\Models\\Acl\\Permission','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"restore:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(68,'Permission','created','App\\Models\\Acl\\Permission','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(69,'Permission','created','App\\Models\\Acl\\Permission','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"import:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(70,'Permission','created','App\\Models\\Acl\\Permission','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"export:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(71,'Permission','created','App\\Models\\Acl\\Permission','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(72,'Permission','created','App\\Models\\Acl\\Permission','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"view:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(73,'Permission','created','App\\Models\\Acl\\Permission','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"create:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(74,'Permission','created','App\\Models\\Acl\\Permission','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"update:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(75,'Permission','created','App\\Models\\Acl\\Permission','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"delete:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(76,'Permission','created','App\\Models\\Acl\\Permission','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"restore:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(77,'Permission','created','App\\Models\\Acl\\Permission','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(78,'Permission','created','App\\Models\\Acl\\Permission','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"import:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(79,'Permission','created','App\\Models\\Acl\\Permission','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"export:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(80,'Permission','created','App\\Models\\Acl\\Permission','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(81,'Permission','created','App\\Models\\Acl\\Permission','created',64,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(82,'Permission','created','App\\Models\\Acl\\Permission','created',65,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnData:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(83,'Permission','created','App\\Models\\Acl\\Permission','created',66,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(84,'Permission','created','App\\Models\\Acl\\Permission','created',67,NULL,NULL,'{\"attributes\": {\"name\": \"view:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(85,'Permission','created','App\\Models\\Acl\\Permission','created',68,NULL,NULL,'{\"attributes\": {\"name\": \"create:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(86,'Permission','created','App\\Models\\Acl\\Permission','created',69,NULL,NULL,'{\"attributes\": {\"name\": \"update:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(87,'Permission','created','App\\Models\\Acl\\Permission','created',70,NULL,NULL,'{\"attributes\": {\"name\": \"delete:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(88,'Permission','created','App\\Models\\Acl\\Permission','created',71,NULL,NULL,'{\"attributes\": {\"name\": \"restore:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(89,'Permission','created','App\\Models\\Acl\\Permission','created',72,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(90,'Permission','created','App\\Models\\Acl\\Permission','created',73,NULL,NULL,'{\"attributes\": {\"name\": \"import:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(91,'Permission','created','App\\Models\\Acl\\Permission','created',74,NULL,NULL,'{\"attributes\": {\"name\": \"export:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(92,'Permission','created','App\\Models\\Acl\\Permission','created',75,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(93,'Permission','created','App\\Models\\Acl\\Permission','created',76,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(94,'Permission','created','App\\Models\\Acl\\Permission','created',77,NULL,NULL,'{\"attributes\": {\"name\": \"root:manage\", \"module_id\": 10, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(95,'Permission','created','App\\Models\\Acl\\Permission','created',78,NULL,NULL,'{\"attributes\": {\"name\": \"view:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(96,'Permission','created','App\\Models\\Acl\\Permission','created',79,NULL,NULL,'{\"attributes\": {\"name\": \"create:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(97,'Permission','created','App\\Models\\Acl\\Permission','created',80,NULL,NULL,'{\"attributes\": {\"name\": \"update:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(98,'Permission','created','App\\Models\\Acl\\Permission','created',81,NULL,NULL,'{\"attributes\": {\"name\": \"delete:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(99,'Permission','created','App\\Models\\Acl\\Permission','created',82,NULL,NULL,'{\"attributes\": {\"name\": \"restore:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(100,'Permission','created','App\\Models\\Acl\\Permission','created',83,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(101,'Permission','created','App\\Models\\Acl\\Permission','created',84,NULL,NULL,'{\"attributes\": {\"name\": \"import:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(102,'Permission','created','App\\Models\\Acl\\Permission','created',85,NULL,NULL,'{\"attributes\": {\"name\": \"export:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(103,'Permission','created','App\\Models\\Acl\\Permission','created',86,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(104,'Permission','created','App\\Models\\Acl\\Permission','created',87,NULL,NULL,'{\"attributes\": {\"name\": \"view:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(105,'Permission','created','App\\Models\\Acl\\Permission','created',88,NULL,NULL,'{\"attributes\": {\"name\": \"create:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(106,'Permission','created','App\\Models\\Acl\\Permission','created',89,NULL,NULL,'{\"attributes\": {\"name\": \"update:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(107,'Permission','created','App\\Models\\Acl\\Permission','created',90,NULL,NULL,'{\"attributes\": {\"name\": \"delete:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(108,'Permission','created','App\\Models\\Acl\\Permission','created',91,NULL,NULL,'{\"attributes\": {\"name\": \"restore:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(109,'Permission','created','App\\Models\\Acl\\Permission','created',92,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(110,'Permission','created','App\\Models\\Acl\\Permission','created',93,NULL,NULL,'{\"attributes\": {\"name\": \"import:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(111,'Permission','created','App\\Models\\Acl\\Permission','created',94,NULL,NULL,'{\"attributes\": {\"name\": \"export:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(112,'Permission','created','App\\Models\\Acl\\Permission','created',95,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(113,'Permission','created','App\\Models\\Acl\\Permission','created',96,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(114,'Permission','created','App\\Models\\Acl\\Permission','created',97,NULL,NULL,'{\"attributes\": {\"name\": \"view:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(115,'Permission','created','App\\Models\\Acl\\Permission','created',98,NULL,NULL,'{\"attributes\": {\"name\": \"create:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(116,'Permission','created','App\\Models\\Acl\\Permission','created',99,NULL,NULL,'{\"attributes\": {\"name\": \"update:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(117,'Permission','created','App\\Models\\Acl\\Permission','created',100,NULL,NULL,'{\"attributes\": {\"name\": \"delete:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(118,'Permission','created','App\\Models\\Acl\\Permission','created',101,NULL,NULL,'{\"attributes\": {\"name\": \"restore:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(119,'Permission','created','App\\Models\\Acl\\Permission','created',102,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(120,'Permission','created','App\\Models\\Acl\\Permission','created',103,NULL,NULL,'{\"attributes\": {\"name\": \"import:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58'),(121,'Permission','created','App\\Models\\Acl\\Permission','created',104,NULL,NULL,'{\"attributes\": {\"name\": \"export:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(122,'Permission','created','App\\Models\\Acl\\Permission','created',105,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(123,'Permission','created','App\\Models\\Acl\\Permission','created',106,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(124,'Permission','created','App\\Models\\Acl\\Permission','created',107,NULL,NULL,'{\"attributes\": {\"name\": \"view:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(125,'Permission','created','App\\Models\\Acl\\Permission','created',108,NULL,NULL,'{\"attributes\": {\"name\": \"create:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(126,'Permission','created','App\\Models\\Acl\\Permission','created',109,NULL,NULL,'{\"attributes\": {\"name\": \"update:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(127,'Permission','created','App\\Models\\Acl\\Permission','created',110,NULL,NULL,'{\"attributes\": {\"name\": \"delete:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(128,'Permission','created','App\\Models\\Acl\\Permission','created',111,NULL,NULL,'{\"attributes\": {\"name\": \"restore:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(129,'Permission','created','App\\Models\\Acl\\Permission','created',112,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(130,'Permission','created','App\\Models\\Acl\\Permission','created',113,NULL,NULL,'{\"attributes\": {\"name\": \"import:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(131,'Permission','created','App\\Models\\Acl\\Permission','created',114,NULL,NULL,'{\"attributes\": {\"name\": \"export:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(132,'Permission','created','App\\Models\\Acl\\Permission','created',115,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(133,'Permission','created','App\\Models\\Acl\\Permission','created',116,NULL,NULL,'{\"attributes\": {\"name\": \"view:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(134,'Permission','created','App\\Models\\Acl\\Permission','created',117,NULL,NULL,'{\"attributes\": {\"name\": \"create:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(135,'Permission','created','App\\Models\\Acl\\Permission','created',118,NULL,NULL,'{\"attributes\": {\"name\": \"update:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(136,'Permission','created','App\\Models\\Acl\\Permission','created',119,NULL,NULL,'{\"attributes\": {\"name\": \"delete:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(137,'Permission','created','App\\Models\\Acl\\Permission','created',120,NULL,NULL,'{\"attributes\": {\"name\": \"restore:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(138,'Permission','created','App\\Models\\Acl\\Permission','created',121,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(139,'Permission','created','App\\Models\\Acl\\Permission','created',122,NULL,NULL,'{\"attributes\": {\"name\": \"import:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(140,'Permission','created','App\\Models\\Acl\\Permission','created',123,NULL,NULL,'{\"attributes\": {\"name\": \"export:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(141,'Permission','created','App\\Models\\Acl\\Permission','created',124,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(142,'Permission','created','App\\Models\\Acl\\Permission','created',125,NULL,NULL,'{\"attributes\": {\"name\": \"view:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(143,'Permission','created','App\\Models\\Acl\\Permission','created',126,NULL,NULL,'{\"attributes\": {\"name\": \"create:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(144,'Permission','created','App\\Models\\Acl\\Permission','created',127,NULL,NULL,'{\"attributes\": {\"name\": \"update:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(145,'Permission','created','App\\Models\\Acl\\Permission','created',128,NULL,NULL,'{\"attributes\": {\"name\": \"delete:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(146,'Permission','created','App\\Models\\Acl\\Permission','created',129,NULL,NULL,'{\"attributes\": {\"name\": \"restore:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(147,'Permission','created','App\\Models\\Acl\\Permission','created',130,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(148,'Permission','created','App\\Models\\Acl\\Permission','created',131,NULL,NULL,'{\"attributes\": {\"name\": \"import:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(149,'Permission','created','App\\Models\\Acl\\Permission','created',132,NULL,NULL,'{\"attributes\": {\"name\": \"export:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(150,'Permission','created','App\\Models\\Acl\\Permission','created',133,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(151,'Permission','created','App\\Models\\Acl\\Permission','created',134,NULL,NULL,'{\"attributes\": {\"name\": \"view:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(152,'Permission','created','App\\Models\\Acl\\Permission','created',135,NULL,NULL,'{\"attributes\": {\"name\": \"create:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(153,'Permission','created','App\\Models\\Acl\\Permission','created',136,NULL,NULL,'{\"attributes\": {\"name\": \"update:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(154,'Permission','created','App\\Models\\Acl\\Permission','created',137,NULL,NULL,'{\"attributes\": {\"name\": \"delete:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(155,'Permission','created','App\\Models\\Acl\\Permission','created',138,NULL,NULL,'{\"attributes\": {\"name\": \"restore:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(156,'Permission','created','App\\Models\\Acl\\Permission','created',139,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(157,'Permission','created','App\\Models\\Acl\\Permission','created',140,NULL,NULL,'{\"attributes\": {\"name\": \"import:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(158,'Permission','created','App\\Models\\Acl\\Permission','created',141,NULL,NULL,'{\"attributes\": {\"name\": \"export:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(159,'Permission','created','App\\Models\\Acl\\Permission','created',142,NULL,NULL,'{\"attributes\": {\"name\": \"viewOwnDashboard:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(160,'Permission','created','App\\Models\\Acl\\Permission','created',143,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentPersonalDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(161,'Permission','created','App\\Models\\Acl\\Permission','created',144,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentProgramDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(162,'Permission','created','App\\Models\\Acl\\Permission','created',145,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentSponsorDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(163,'Permission','created','App\\Models\\Acl\\Permission','created',146,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentContactDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(164,'Permission','created','App\\Models\\Acl\\Permission','created',147,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentFinancialDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(165,'Permission','created','App\\Models\\Acl\\Permission','created',148,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentAcademicDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(166,'Permission','created','App\\Models\\Acl\\Permission','created',149,NULL,NULL,'{\"attributes\": {\"name\": \"manageStudentMetadata:admin\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(167,'Role','created','App\\Models\\Acl\\Role','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super Administrator\", \"guard_name\": \"web\", \"description\": \"Has full access to all system features and administrative controls.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(168,'Role','created','App\\Models\\Acl\\Role','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Principal\", \"guard_name\": \"web\", \"description\": \"The chief executive of the college, responsible for overall leadership.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(169,'Role','created','App\\Models\\Acl\\Role','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Vice Principal\", \"guard_name\": \"web\", \"description\": \"Assists the principal in managing academic and administrative tasks.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(170,'Role','created','App\\Models\\Acl\\Role','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Registrar\", \"guard_name\": \"web\", \"description\": \"Oversees student records, registration, and institutional data.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(171,'Role','created','App\\Models\\Acl\\Role','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Selection officer\", \"guard_name\": \"web\", \"description\": \"Manages applicant evaluation and admission selections.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(172,'Role','created','App\\Models\\Acl\\Role','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Dean\", \"guard_name\": \"web\", \"description\": \"Leads an academic faculty and manages teaching and research efforts.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(173,'Role','created','App\\Models\\Acl\\Role','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Head of department\", \"guard_name\": \"web\", \"description\": \"Directs the operations of a specific academic department.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(174,'Role','created','App\\Models\\Acl\\Role','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Head of division\", \"guard_name\": \"web\", \"description\": \"Oversees a group of departments within a school or faculty.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(175,'Role','created','App\\Models\\Acl\\Role','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Lecturer\", \"guard_name\": \"web\", \"description\": \"Delivers lectures and academic content to students.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(176,'Role','created','App\\Models\\Acl\\Role','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Lecturer In Charge\", \"guard_name\": \"web\", \"description\": \"Leads a teaching team and manages course delivery.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(177,'Role','created','App\\Models\\Acl\\Role','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Senior Lecturer\", \"guard_name\": \"web\", \"description\": \"Experienced academic with additional teaching and mentoring responsibilities.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(178,'Role','created','App\\Models\\Acl\\Role','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Tutor\", \"guard_name\": \"web\", \"description\": \"Supports student learning in small group or individual settings.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(179,'Role','created','App\\Models\\Acl\\Role','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Researcher\", \"guard_name\": \"web\", \"description\": \"Conducts academic research and publishes scholarly work.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(180,'Role','created','App\\Models\\Acl\\Role','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Admissions officer\", \"guard_name\": \"web\", \"description\": \"Handles student applications and enrollment processing.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(181,'Role','created','App\\Models\\Acl\\Role','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Bursar\", \"guard_name\": \"web\", \"description\": \"Manages college finances, budgeting, and student billing.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(182,'Role','created','App\\Models\\Acl\\Role','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"HR officer\", \"guard_name\": \"web\", \"description\": \"Administers staff recruitment, payroll, and compliance.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(183,'Role','created','App\\Models\\Acl\\Role','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Administrative Assistant\", \"guard_name\": \"web\", \"description\": \"Provides clerical and logistical support to departments.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(184,'Role','created','App\\Models\\Acl\\Role','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Student Affairs Officer\", \"guard_name\": \"web\", \"description\": \"Supports student life, welfare, and extracurricular engagement.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(185,'Role','created','App\\Models\\Acl\\Role','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"IT Support Technician\", \"guard_name\": \"web\", \"description\": \"Maintains IT infrastructure and provides user support.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(186,'Role','created','App\\Models\\Acl\\Role','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Lab Technician\", \"guard_name\": \"web\", \"description\": \"Prepares lab equipment and assists with practical sessions.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(187,'Role','created','App\\Models\\Acl\\Role','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Librarian\", \"guard_name\": \"web\", \"description\": \"Manages library resources and supports academic research.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(188,'Role','created','App\\Models\\Acl\\Role','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Maintenance staff\", \"guard_name\": \"web\", \"description\": \"Performs repairs and ensures facility upkeep.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(189,'Role','created','App\\Models\\Acl\\Role','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Security officer\", \"guard_name\": \"web\", \"description\": \"Provides safety and security across campus.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(190,'Role','created','App\\Models\\Acl\\Role','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Custodian\", \"guard_name\": \"web\", \"description\": \"Maintains cleanliness and order in college buildings.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(191,'Role','created','App\\Models\\Acl\\Role','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Grounds keeper\", \"guard_name\": \"web\", \"description\": \"Takes care of lawns, gardens, and outdoor spaces.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(192,'Role','created','App\\Models\\Acl\\Role','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Transport Officer\", \"guard_name\": \"web\", \"description\": \"Coordinates campus transport and vehicle logistics.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(193,'Role','created','App\\Models\\Acl\\Role','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Student\", \"guard_name\": \"web\", \"description\": \"A learner enrolled in the institution’s academic programs.\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(194,'Gender','created','App\\Models\\Shared\\Gender','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Male\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(195,'Gender','created','App\\Models\\Shared\\Gender','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Female\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(196,'Title','created','App\\Models\\Shared\\Title','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Mr\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(197,'Title','created','App\\Models\\Shared\\Title','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Mrs\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(198,'Title','created','App\\Models\\Shared\\Title','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Miss\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(199,'Title','created','App\\Models\\Shared\\Title','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Dr\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(200,'Title','created','App\\Models\\Shared\\Title','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Prof\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(201,'Race','created','App\\Models\\Shared\\Race','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"African\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(202,'Race','created','App\\Models\\Shared\\Race','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Black\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(203,'Race','created','App\\Models\\Shared\\Race','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"White\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(204,'Race','created','App\\Models\\Shared\\Race','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Colored\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(205,'Race','created','App\\Models\\Shared\\Race','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Indian\", \"description\": null}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(206,'Status','created','App\\Models\\Shared\\Status','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Active\", \"description\": \"Currently active and in use\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(207,'Status','created','App\\Models\\Shared\\Status','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Waiting Approval\", \"description\": \"Pending approval from an authority\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(208,'Status','created','App\\Models\\Shared\\Status','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Inactive\", \"description\": \"Not currently active\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(209,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Draft / Incomplete\", \"position\": 1, \"description\": \"Application started but not completed\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(210,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Submitted\", \"position\": 2, \"description\": \"Application has been submitted for review\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(211,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"In Review\", \"position\": 3, \"description\": \"Application is currently being reviewed\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(212,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting Requirements\", \"position\": 4, \"description\": \"Waiting for required documents or information\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(213,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting Payment\", \"position\": 5, \"description\": \"Waiting for application fee payment\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(214,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Interview Scheduled\", \"position\": 6, \"description\": \"Interview has been scheduled\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(215,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Interview Completed\", \"position\": 7, \"description\": \"Interview has been completed\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(216,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Decision Pending\", \"position\": 8, \"description\": \"Final decision is being prepared\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(217,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Accepted / Offer Made\", \"position\": 9, \"description\": \"Offer of acceptance has been made\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(218,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Waitlisted\", \"position\": 10, \"description\": \"Application is on the waitlist\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(219,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Rejected\", \"position\": 11, \"description\": \"Application was not successful\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(220,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Offer Accepted\", \"position\": 12, \"description\": \"Offer has been accepted by the applicant\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(221,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Offer Declined\", \"position\": 13, \"description\": \"Offer has been declined by the applicant\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(222,'ApplicationStep','created','App\\Models\\Shared\\ApplicationStep','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Enrolled / Registered\", \"position\": 14, \"description\": \"Applicant has enrolled and registered successfully\"}}',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59'),(223,'User','created','App\\Models\\Users\\User','created',1,NULL,NULL,'{\"attributes\": {\"email\": \"penstejdevelopers@gmail.com\", \"password\": \"$2y$12$27HLVk0QdV5lJ39Y/a5KUOzcw0kEeL0rJNCow6YomynY0orV/em26\", \"avatar_id\": null, \"last_name\": \"Administrator\", \"status_id\": 1, \"tenant_id\": 1, \"first_name\": \"Super\", \"login_count\": 0, \"middle_name\": \"\", \"phone_number\": \"+27788104809\", \"last_login_at\": null, \"email_verified_at\": \"2025-07-04T08:51:59.000000Z\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(224,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Email\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(225,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Sms\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(226,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Phone\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(227,'Country','created','App\\Models\\Shared\\Country','created',1,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Afghanistan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(228,'Country','created','App\\Models\\Shared\\Country','created',2,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Albania\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(229,'Country','created','App\\Models\\Shared\\Country','created',3,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Algeria\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(230,'Country','created','App\\Models\\Shared\\Country','created',4,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Andorra\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(231,'Country','created','App\\Models\\Shared\\Country','created',5,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Angola\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(232,'Country','created','App\\Models\\Shared\\Country','created',6,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Antigua and Barbuda\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(233,'Country','created','App\\Models\\Shared\\Country','created',7,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Argentina\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(234,'Country','created','App\\Models\\Shared\\Country','created',8,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Armenia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(235,'Country','created','App\\Models\\Shared\\Country','created',9,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Australia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(236,'Country','created','App\\Models\\Shared\\Country','created',10,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Azerbaijan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(237,'Country','created','App\\Models\\Shared\\Country','created',11,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bahamas\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(238,'Country','created','App\\Models\\Shared\\Country','created',12,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bahrain\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(239,'Country','created','App\\Models\\Shared\\Country','created',13,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bangladesh\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(240,'Country','created','App\\Models\\Shared\\Country','created',14,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Barbados\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(241,'Country','created','App\\Models\\Shared\\Country','created',15,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belgium\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(242,'Country','created','App\\Models\\Shared\\Country','created',16,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belize\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(243,'Country','created','App\\Models\\Shared\\Country','created',17,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Benin\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(244,'Country','created','App\\Models\\Shared\\Country','created',18,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bhutan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(245,'Country','created','App\\Models\\Shared\\Country','created',19,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bolivia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(246,'Country','created','App\\Models\\Shared\\Country','created',20,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bosnia and Herzegovina\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(247,'Country','created','App\\Models\\Shared\\Country','created',21,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Botswana\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(248,'Country','created','App\\Models\\Shared\\Country','created',22,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Brazil\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(249,'Country','created','App\\Models\\Shared\\Country','created',23,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Brunei\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(250,'Country','created','App\\Models\\Shared\\Country','created',24,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bulgaria\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(251,'Country','created','App\\Models\\Shared\\Country','created',25,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Burkina Faso\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(252,'Country','created','App\\Models\\Shared\\Country','created',26,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Burundi\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(253,'Country','created','App\\Models\\Shared\\Country','created',27,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Chile\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(254,'Country','created','App\\Models\\Shared\\Country','created',28,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Colombia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(255,'Country','created','App\\Models\\Shared\\Country','created',29,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Comoros\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(256,'Country','created','App\\Models\\Shared\\Country','created',30,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Costa Rica\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(257,'Country','created','App\\Models\\Shared\\Country','created',31,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Democratic Republic of the Congo\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(258,'Country','created','App\\Models\\Shared\\Country','created',32,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Denmark\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(259,'Country','created','App\\Models\\Shared\\Country','created',33,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Djibouti\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(260,'Country','created','App\\Models\\Shared\\Country','created',34,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Dominica\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(261,'Country','created','App\\Models\\Shared\\Country','created',35,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Dominican Republic\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(262,'Country','created','App\\Models\\Shared\\Country','created',36,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Germany\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(263,'Country','created','App\\Models\\Shared\\Country','created',37,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ecuador\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(264,'Country','created','App\\Models\\Shared\\Country','created',38,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Egypt\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(265,'Country','created','App\\Models\\Shared\\Country','created',39,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Equatorial Guinea\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(266,'Country','created','App\\Models\\Shared\\Country','created',40,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"El Salvador\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(267,'Country','created','App\\Models\\Shared\\Country','created',41,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Eritrea\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(268,'Country','created','App\\Models\\Shared\\Country','created',42,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Estonia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(269,'Country','created','App\\Models\\Shared\\Country','created',43,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ethiopia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(270,'Country','created','App\\Models\\Shared\\Country','created',44,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Fiji\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(271,'Country','created','App\\Models\\Shared\\Country','created',45,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Philippines\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(272,'Country','created','App\\Models\\Shared\\Country','created',46,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Finland\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(273,'Country','created','App\\Models\\Shared\\Country','created',47,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"France\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(274,'Country','created','App\\Models\\Shared\\Country','created',48,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Gabon\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(275,'Country','created','App\\Models\\Shared\\Country','created',49,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Gambia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(276,'Country','created','App\\Models\\Shared\\Country','created',50,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Georgia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(277,'Country','created','App\\Models\\Shared\\Country','created',51,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ghana\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(278,'Country','created','App\\Models\\Shared\\Country','created',52,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Grenada\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(279,'Country','created','App\\Models\\Shared\\Country','created',53,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Greece\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(280,'Country','created','App\\Models\\Shared\\Country','created',54,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guatemala\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(281,'Country','created','App\\Models\\Shared\\Country','created',55,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guinea\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(282,'Country','created','App\\Models\\Shared\\Country','created',56,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guinea-Bissau\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(283,'Country','created','App\\Models\\Shared\\Country','created',57,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guyana\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(284,'Country','created','App\\Models\\Shared\\Country','created',58,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Haiti\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(285,'Country','created','App\\Models\\Shared\\Country','created',59,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Honduras\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(286,'Country','created','App\\Models\\Shared\\Country','created',60,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Hungary\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(287,'Country','created','App\\Models\\Shared\\Country','created',61,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ireland\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(288,'Country','created','App\\Models\\Shared\\Country','created',62,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"India\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(289,'Country','created','App\\Models\\Shared\\Country','created',63,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Indonesia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(290,'Country','created','App\\Models\\Shared\\Country','created',64,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iran\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(291,'Country','created','App\\Models\\Shared\\Country','created',65,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iraq\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(292,'Country','created','App\\Models\\Shared\\Country','created',66,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Israel\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(293,'Country','created','App\\Models\\Shared\\Country','created',67,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Italy\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(294,'Country','created','App\\Models\\Shared\\Country','created',68,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Côte d’Ivoire\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(295,'Country','created','App\\Models\\Shared\\Country','created',69,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Jamaica\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(296,'Country','created','App\\Models\\Shared\\Country','created',70,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Japan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(297,'Country','created','App\\Models\\Shared\\Country','created',71,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Yemen\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(298,'Country','created','App\\Models\\Shared\\Country','created',72,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Jordan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(299,'Country','created','App\\Models\\Shared\\Country','created',73,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cape Verde Islands\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(300,'Country','created','App\\Models\\Shared\\Country','created',74,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cambodia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(301,'Country','created','App\\Models\\Shared\\Country','created',75,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cameroon\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(302,'Country','created','App\\Models\\Shared\\Country','created',76,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Canada\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(303,'Country','created','App\\Models\\Shared\\Country','created',77,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kazakhstan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(304,'Country','created','App\\Models\\Shared\\Country','created',78,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Qatar\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(305,'Country','created','App\\Models\\Shared\\Country','created',79,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kenya\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(306,'Country','created','App\\Models\\Shared\\Country','created',80,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kyrgyzstan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(307,'Country','created','App\\Models\\Shared\\Country','created',81,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kiribati\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(308,'Country','created','App\\Models\\Shared\\Country','created',82,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kuwait\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(309,'Country','created','App\\Models\\Shared\\Country','created',83,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Croatia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(310,'Country','created','App\\Models\\Shared\\Country','created',84,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cuba\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(311,'Country','created','App\\Models\\Shared\\Country','created',85,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Laos\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(312,'Country','created','App\\Models\\Shared\\Country','created',86,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Latvia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(313,'Country','created','App\\Models\\Shared\\Country','created',87,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lesotho\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(314,'Country','created','App\\Models\\Shared\\Country','created',88,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lebanon\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(315,'Country','created','App\\Models\\Shared\\Country','created',89,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Liberia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(316,'Country','created','App\\Models\\Shared\\Country','created',90,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Libya\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(317,'Country','created','App\\Models\\Shared\\Country','created',91,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Liechtenstein\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(318,'Country','created','App\\Models\\Shared\\Country','created',92,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lithuania\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(319,'Country','created','App\\Models\\Shared\\Country','created',93,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Luxembourg\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(320,'Country','created','App\\Models\\Shared\\Country','created',94,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Madagascar\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(321,'Country','created','App\\Models\\Shared\\Country','created',95,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malawi\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(322,'Country','created','App\\Models\\Shared\\Country','created',96,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malaysia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(323,'Country','created','App\\Models\\Shared\\Country','created',97,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Maldives\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(324,'Country','created','App\\Models\\Shared\\Country','created',98,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mali\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(325,'Country','created','App\\Models\\Shared\\Country','created',99,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malta\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(326,'Country','created','App\\Models\\Shared\\Country','created',100,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Morocco\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(327,'Country','created','App\\Models\\Shared\\Country','created',101,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Marshall Islands\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(328,'Country','created','App\\Models\\Shared\\Country','created',102,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mauritania\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(329,'Country','created','App\\Models\\Shared\\Country','created',103,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mauritius\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(330,'Country','created','App\\Models\\Shared\\Country','created',104,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mexico\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(331,'Country','created','App\\Models\\Shared\\Country','created',105,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Myanmar (Burma)\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(332,'Country','created','App\\Models\\Shared\\Country','created',106,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Micronesia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(333,'Country','created','App\\Models\\Shared\\Country','created',107,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Moldova\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(334,'Country','created','App\\Models\\Shared\\Country','created',108,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Monaco\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(335,'Country','created','App\\Models\\Shared\\Country','created',109,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mongolia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(336,'Country','created','App\\Models\\Shared\\Country','created',110,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Montenegro\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(337,'Country','created','App\\Models\\Shared\\Country','created',111,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mozambique\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(338,'Country','created','App\\Models\\Shared\\Country','created',112,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Namibia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(339,'Country','created','App\\Models\\Shared\\Country','created',113,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nauru\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(340,'Country','created','App\\Models\\Shared\\Country','created',114,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nepal\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(341,'Country','created','App\\Models\\Shared\\Country','created',115,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Netherlands\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(342,'Country','created','App\\Models\\Shared\\Country','created',116,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"New Zealand\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(343,'Country','created','App\\Models\\Shared\\Country','created',117,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nicaragua\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(344,'Country','created','App\\Models\\Shared\\Country','created',118,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Niger\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(345,'Country','created','App\\Models\\Shared\\Country','created',119,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nigeria\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(346,'Country','created','App\\Models\\Shared\\Country','created',120,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"North Korea\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(347,'Country','created','App\\Models\\Shared\\Country','created',121,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Norway\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(348,'Country','created','App\\Models\\Shared\\Country','created',122,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ukraine\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(349,'Country','created','App\\Models\\Shared\\Country','created',123,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uzbekistan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(350,'Country','created','App\\Models\\Shared\\Country','created',124,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Oman\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(351,'Country','created','App\\Models\\Shared\\Country','created',125,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Austria\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(352,'Country','created','App\\Models\\Shared\\Country','created',126,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"East Timor\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(353,'Country','created','App\\Models\\Shared\\Country','created',127,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Pakistan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(354,'Country','created','App\\Models\\Shared\\Country','created',128,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Palau\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(355,'Country','created','App\\Models\\Shared\\Country','created',129,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Panama\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(356,'Country','created','App\\Models\\Shared\\Country','created',130,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Papua New Guinea\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(357,'Country','created','App\\Models\\Shared\\Country','created',131,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Paraguay\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(358,'Country','created','App\\Models\\Shared\\Country','created',132,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Peru\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(359,'Country','created','App\\Models\\Shared\\Country','created',133,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Pole/Poland\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(360,'Country','created','App\\Models\\Shared\\Country','created',134,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Portugal\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(361,'Country','created','App\\Models\\Shared\\Country','created',135,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Republic of the Congo\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(362,'Country','created','App\\Models\\Shared\\Country','created',136,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Macedonia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(363,'Country','created','App\\Models\\Shared\\Country','created',137,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Romania\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(364,'Country','created','App\\Models\\Shared\\Country','created',138,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Russia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(365,'Country','created','App\\Models\\Shared\\Country','created',139,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Rwanda\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(366,'Country','created','App\\Models\\Shared\\Country','created',140,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Samoa\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(367,'Country','created','App\\Models\\Shared\\Country','created',141,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"San Marino\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(368,'Country','created','App\\Models\\Shared\\Country','created',142,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Saudi Arabia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(369,'Country','created','App\\Models\\Shared\\Country','created',143,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"São Tomé and Principe\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(370,'Country','created','App\\Models\\Shared\\Country','created',144,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Senegal\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(371,'Country','created','App\\Models\\Shared\\Country','created',145,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Central African Republic\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(372,'Country','created','App\\Models\\Shared\\Country','created',146,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Serbia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(373,'Country','created','App\\Models\\Shared\\Country','created',147,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Seychelles\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(374,'Country','created','App\\Models\\Shared\\Country','created',148,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"China\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(375,'Country','created','App\\Models\\Shared\\Country','created',149,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sierra Leone\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(376,'Country','created','App\\Models\\Shared\\Country','created',150,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Singapore\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(377,'Country','created','App\\Models\\Shared\\Country','created',151,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cyprus\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(378,'Country','created','App\\Models\\Shared\\Country','created',152,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Syria\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(379,'Country','created','App\\Models\\Shared\\Country','created',153,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Slovakia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(380,'Country','created','App\\Models\\Shared\\Country','created',154,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Slovenia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(381,'Country','created','App\\Models\\Shared\\Country','created',155,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sudan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(382,'Country','created','App\\Models\\Shared\\Country','created',156,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Solomon Islands\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(383,'Country','created','App\\Models\\Shared\\Country','created',157,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Somalia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(384,'Country','created','App\\Models\\Shared\\Country','created',158,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Spain\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(385,'Country','created','App\\Models\\Shared\\Country','created',159,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sri Lanka\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(386,'Country','created','App\\Models\\Shared\\Country','created',160,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Saint Kitts and Nevis\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(387,'Country','created','App\\Models\\Shared\\Country','created',161,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"St. Lucia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(388,'Country','created','App\\Models\\Shared\\Country','created',162,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"St. Vincent and the Grenadines\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(389,'Country','created','App\\Models\\Shared\\Country','created',163,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"South Africa\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(390,'Country','created','App\\Models\\Shared\\Country','created',164,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Suid-Korea/South Korea\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(391,'Country','created','App\\Models\\Shared\\Country','created',165,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"South Sudan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(392,'Country','created','App\\Models\\Shared\\Country','created',166,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Suriname\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(393,'Country','created','App\\Models\\Shared\\Country','created',167,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Swaziland\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(394,'Country','created','App\\Models\\Shared\\Country','created',168,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sweden\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(395,'Country','created','App\\Models\\Shared\\Country','created',169,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Switzerland\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(396,'Country','created','App\\Models\\Shared\\Country','created',170,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tajikistan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(397,'Country','created','App\\Models\\Shared\\Country','created',171,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Taiwan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(398,'Country','created','App\\Models\\Shared\\Country','created',172,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tanzania\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(399,'Country','created','App\\Models\\Shared\\Country','created',173,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Thailand\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(400,'Country','created','App\\Models\\Shared\\Country','created',174,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tonga\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(401,'Country','created','App\\Models\\Shared\\Country','created',175,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Togo\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(402,'Country','created','App\\Models\\Shared\\Country','created',176,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Trinidad and Tobago\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(403,'Country','created','App\\Models\\Shared\\Country','created',177,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Chad\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(404,'Country','created','App\\Models\\Shared\\Country','created',178,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Czech Republic\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(405,'Country','created','App\\Models\\Shared\\Country','created',179,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tunisia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(406,'Country','created','App\\Models\\Shared\\Country','created',180,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Turkmenistan\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(407,'Country','created','App\\Models\\Shared\\Country','created',181,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Turkey\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(408,'Country','created','App\\Models\\Shared\\Country','created',182,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tuvalu\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(409,'Country','created','App\\Models\\Shared\\Country','created',183,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uganda\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(410,'Country','created','App\\Models\\Shared\\Country','created',184,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uruguay\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(411,'Country','created','App\\Models\\Shared\\Country','created',185,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Vanuatu\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(412,'Country','created','App\\Models\\Shared\\Country','created',186,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Venezuela\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(413,'Country','created','App\\Models\\Shared\\Country','created',187,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United Arab Emirates\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(414,'Country','created','App\\Models\\Shared\\Country','created',188,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United Kingdom\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(415,'Country','created','App\\Models\\Shared\\Country','created',189,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United States of America\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(416,'Country','created','App\\Models\\Shared\\Country','created',190,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Vietnam\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(417,'Country','created','App\\Models\\Shared\\Country','created',191,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belarus\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(418,'Country','created','App\\Models\\Shared\\Country','created',192,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iceland\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(419,'Country','created','App\\Models\\Shared\\Country','created',193,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Zambia\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(420,'Country','created','App\\Models\\Shared\\Country','created',194,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Zimbabwe\"}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(421,'Religion','created','App\\Models\\Shared\\Religion','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Christianity\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(422,'Religion','created','App\\Models\\Shared\\Religion','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"African Traditional Religion\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(423,'Religion','created','App\\Models\\Shared\\Religion','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Islam\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(424,'Religion','created','App\\Models\\Shared\\Religion','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Hinduism\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(425,'Religion','created','App\\Models\\Shared\\Religion','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Buddhism\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(426,'Religion','created','App\\Models\\Shared\\Religion','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Judaism\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(427,'Religion','created','App\\Models\\Shared\\Religion','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Other Religions\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(428,'Religion','created','App\\Models\\Shared\\Religion','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Religiously Unaffiliated\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(429,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Monthly\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(430,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Annually\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(431,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Once off\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(432,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Credit Card\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(433,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Cash Payment\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(434,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Debit Order\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(435,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"EFT\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(436,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Stop Order\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(437,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"1\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(438,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"2\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(439,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"3\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(440,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"4\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(441,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"5\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(442,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"6\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(443,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"7\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(444,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',8,NULL,NULL,'{\"attributes\": {\"title\": \"8\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(445,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',9,NULL,NULL,'{\"attributes\": {\"title\": \"9\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(446,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',10,NULL,NULL,'{\"attributes\": {\"title\": \"10\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(447,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',11,NULL,NULL,'{\"attributes\": {\"title\": \"11\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(448,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',12,NULL,NULL,'{\"attributes\": {\"title\": \"12\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(449,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',13,NULL,NULL,'{\"attributes\": {\"title\": \"13\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(450,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',14,NULL,NULL,'{\"attributes\": {\"title\": \"14\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(451,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',15,NULL,NULL,'{\"attributes\": {\"title\": \"15\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(452,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',16,NULL,NULL,'{\"attributes\": {\"title\": \"16\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(453,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',17,NULL,NULL,'{\"attributes\": {\"title\": \"17\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(454,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',18,NULL,NULL,'{\"attributes\": {\"title\": \"18\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(455,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',19,NULL,NULL,'{\"attributes\": {\"title\": \"19\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(456,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',20,NULL,NULL,'{\"attributes\": {\"title\": \"20\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(457,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',21,NULL,NULL,'{\"attributes\": {\"title\": \"21\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(458,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',22,NULL,NULL,'{\"attributes\": {\"title\": \"22\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(459,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',23,NULL,NULL,'{\"attributes\": {\"title\": \"23\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(460,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',24,NULL,NULL,'{\"attributes\": {\"title\": \"24\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(461,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',25,NULL,NULL,'{\"attributes\": {\"title\": \"25\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(462,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',26,NULL,NULL,'{\"attributes\": {\"title\": \"26\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(463,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',27,NULL,NULL,'{\"attributes\": {\"title\": \"27\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(464,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',28,NULL,NULL,'{\"attributes\": {\"title\": \"28\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(465,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',29,NULL,NULL,'{\"attributes\": {\"title\": \"29\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(466,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',30,NULL,NULL,'{\"attributes\": {\"title\": \"30\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(467,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',31,NULL,NULL,'{\"attributes\": {\"title\": \"31\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(468,'Language','created','App\\Models\\Shared\\Language','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"English\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(469,'Province','created','App\\Models\\Shared\\Province','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Bulawayo\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(470,'Province','created','App\\Models\\Shared\\Province','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Harare\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(471,'Province','created','App\\Models\\Shared\\Province','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Manicaland\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(472,'Province','created','App\\Models\\Shared\\Province','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland Central\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(473,'Province','created','App\\Models\\Shared\\Province','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland East\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(474,'Province','created','App\\Models\\Shared\\Province','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland West\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(475,'Province','created','App\\Models\\Shared\\Province','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"Masvingo\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(476,'Province','created','App\\Models\\Shared\\Province','created',8,NULL,NULL,'{\"attributes\": {\"title\": \"Matebeleland North\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(477,'Province','created','App\\Models\\Shared\\Province','created',9,NULL,NULL,'{\"attributes\": {\"title\": \"Matebeleland South\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(478,'Province','created','App\\Models\\Shared\\Province','created',10,NULL,NULL,'{\"attributes\": {\"title\": \"Midlands\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(479,'Province','created','App\\Models\\Shared\\Province','created',11,NULL,NULL,'{\"attributes\": {\"title\": \"Unknown Province\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(480,'District','created','App\\Models\\Shared\\District','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Bulawayo\", \"description\": null, \"province_id\": 1}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(481,'District','created','App\\Models\\Shared\\District','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Chitungwiza\", \"description\": null, \"province_id\": 2}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(482,'District','created','App\\Models\\Shared\\District','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Harare\", \"description\": null, \"province_id\": 2}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(483,'District','created','App\\Models\\Shared\\District','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Buhera\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(484,'District','created','App\\Models\\Shared\\District','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Chimanimani\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(485,'District','created','App\\Models\\Shared\\District','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Chipinge\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(486,'District','created','App\\Models\\Shared\\District','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Makoni\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(487,'District','created','App\\Models\\Shared\\District','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Mutare\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(488,'District','created','App\\Models\\Shared\\District','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Mutasa\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(489,'District','created','App\\Models\\Shared\\District','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Nyanga\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(490,'District','created','App\\Models\\Shared\\District','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Bindura\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(491,'District','created','App\\Models\\Shared\\District','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Guruve\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(492,'District','created','App\\Models\\Shared\\District','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Mazowe\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(493,'District','created','App\\Models\\Shared\\District','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Mbire\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(494,'District','created','App\\Models\\Shared\\District','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Mount Darwin\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(495,'District','created','App\\Models\\Shared\\District','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Muzarabani\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(496,'District','created','App\\Models\\Shared\\District','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Rushinga\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(497,'District','created','App\\Models\\Shared\\District','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Shamva\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(498,'District','created','App\\Models\\Shared\\District','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Chikomba\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(499,'District','created','App\\Models\\Shared\\District','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Goromonzi\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(500,'District','created','App\\Models\\Shared\\District','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Marondera\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(501,'District','created','App\\Models\\Shared\\District','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Mudzi\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(502,'District','created','App\\Models\\Shared\\District','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Murehwa\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(503,'District','created','App\\Models\\Shared\\District','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Mutoko\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(504,'District','created','App\\Models\\Shared\\District','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Seke\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(505,'District','created','App\\Models\\Shared\\District','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"UMP (Uzumba-Maramba-Pfungwe)\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(506,'District','created','App\\Models\\Shared\\District','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Wedza (Hwedza)\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(507,'District','created','App\\Models\\Shared\\District','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Chegutu\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(508,'District','created','App\\Models\\Shared\\District','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Hurungwe\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(509,'District','created','App\\Models\\Shared\\District','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Kariba\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(510,'District','created','App\\Models\\Shared\\District','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Makonde\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(511,'District','created','App\\Models\\Shared\\District','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Mhondoro-Ngezi\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(512,'District','created','App\\Models\\Shared\\District','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"Sanyati\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(513,'District','created','App\\Models\\Shared\\District','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"Zvimba\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(514,'District','created','App\\Models\\Shared\\District','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"Bikita\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(515,'District','created','App\\Models\\Shared\\District','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"Chiredzi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(516,'District','created','App\\Models\\Shared\\District','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"Chivi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(517,'District','created','App\\Models\\Shared\\District','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"Gutu\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(518,'District','created','App\\Models\\Shared\\District','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"Masvingo\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(519,'District','created','App\\Models\\Shared\\District','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"Mwenezi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(520,'District','created','App\\Models\\Shared\\District','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"Zaka\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(521,'District','created','App\\Models\\Shared\\District','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"Binga\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(522,'District','created','App\\Models\\Shared\\District','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"Bubi\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(523,'District','created','App\\Models\\Shared\\District','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"Hwange\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(524,'District','created','App\\Models\\Shared\\District','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"Lupane\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(525,'District','created','App\\Models\\Shared\\District','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"Nkayi\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(526,'District','created','App\\Models\\Shared\\District','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"Tsholotsho\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(527,'District','created','App\\Models\\Shared\\District','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"Umguza\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(528,'District','created','App\\Models\\Shared\\District','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"Beitbridge\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(529,'District','created','App\\Models\\Shared\\District','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"Bulilima\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(530,'District','created','App\\Models\\Shared\\District','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"Gwanda\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(531,'District','created','App\\Models\\Shared\\District','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"Insiza\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(532,'District','created','App\\Models\\Shared\\District','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"Mangwe\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(533,'District','created','App\\Models\\Shared\\District','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"Matobo\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(534,'District','created','App\\Models\\Shared\\District','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"Umzingwane\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(535,'District','created','App\\Models\\Shared\\District','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"Chirumhanzu\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(536,'District','created','App\\Models\\Shared\\District','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"Gokwe North\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(537,'District','created','App\\Models\\Shared\\District','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"Gokwe South\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(538,'District','created','App\\Models\\Shared\\District','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"Gweru\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(539,'District','created','App\\Models\\Shared\\District','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"Kwekwe\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(540,'District','created','App\\Models\\Shared\\District','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"Mberengwa\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(541,'District','created','App\\Models\\Shared\\District','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"Shurugwi\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(542,'District','created','App\\Models\\Shared\\District','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"Zvishavane\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(543,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Person\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(544,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Company\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(545,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Church\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(546,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Other Organization\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(547,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Divorced\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(548,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Engaged\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(549,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Married\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(550,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Single\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(551,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Widowed\", \"description\": null}}',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00'),(552,'AddressType','created','App\\Models\\Shared\\AddressType','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"business\", \"title\": \"Business\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(553,'AddressType','created','App\\Models\\Shared\\AddressType','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"complex\", \"title\": \"Complex\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(554,'AddressType','created','App\\Models\\Shared\\AddressType','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"home\", \"title\": \"Home\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(555,'AddressType','created','App\\Models\\Shared\\AddressType','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"physical\", \"title\": \"Physical\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(556,'AddressType','created','App\\Models\\Shared\\AddressType','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"postal\", \"title\": \"Postal\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(557,'Course','created','App\\Models\\Institution\\Course','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Beauty Therapy\", \"position\": 1, \"description\": \"Applied Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(558,'Course','created','App\\Models\\Institution\\Course','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Cosmetology\", \"position\": 2, \"description\": \"Applied Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(559,'Course','created','App\\Models\\Institution\\Course','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Hairdressing\", \"position\": 3, \"description\": \"Applied Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(560,'Course','created','App\\Models\\Institution\\Course','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Industrial Clothing Design and Construction Design\", \"position\": 4, \"description\": \"Applied Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(561,'Course','created','App\\Models\\Institution\\Course','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Biological Technology\", \"position\": 5, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(562,'Course','created','App\\Models\\Institution\\Course','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Chemical Technology\", \"position\": 6, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(563,'Course','created','App\\Models\\Institution\\Course','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Chemical Engineering\", \"position\": 7, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(564,'Course','created','App\\Models\\Institution\\Course','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Chemical Technology\", \"position\": 8, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(565,'Course','created','App\\Models\\Institution\\Course','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Food Science\", \"position\": 9, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(566,'Course','created','App\\Models\\Institution\\Course','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Horticulture\", \"position\": 10, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(567,'Course','created','App\\Models\\Institution\\Course','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Laboratory Technology\", \"position\": 11, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(568,'Course','created','App\\Models\\Institution\\Course','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Metallurgical Assaying\", \"position\": 12, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(569,'Course','created','App\\Models\\Institution\\Course','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Pharmaceutical Technology\", \"position\": 13, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(570,'Course','created','App\\Models\\Institution\\Course','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Polymer Technology\", \"position\": 14, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(571,'Course','created','App\\Models\\Institution\\Course','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Automobile Electrics And Electronics\", \"position\": 15, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(572,'Course','created','App\\Models\\Institution\\Course','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Engineering\", \"position\": 16, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(573,'Course','created','App\\Models\\Institution\\Course','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Precision Machining\", \"position\": 17, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(574,'Course','created','App\\Models\\Institution\\Course','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Diesel Plant Fitting\", \"position\": 18, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(575,'Course','created','App\\Models\\Institution\\Course','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Cycle Machining\", \"position\": 19, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(576,'Course','created','App\\Models\\Institution\\Course','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Vehicle Body Repairs\", \"position\": 20, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(577,'Course','created','App\\Models\\Institution\\Course','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Vehicle Mechanics\", \"position\": 21, \"description\": \"Automotive\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(578,'Course','created','App\\Models\\Institution\\Course','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Accountancy\", \"position\": 22, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(579,'Course','created','App\\Models\\Institution\\Course','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Banking and Finance\", \"position\": 23, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(580,'Course','created','App\\Models\\Institution\\Course','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Health Services Management\", \"position\": 24, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(581,'Course','created','App\\Models\\Institution\\Course','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Human Resources Management\", \"position\": 25, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(582,'Course','created','App\\Models\\Institution\\Course','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Pensions & Investments Management\", \"position\": 26, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(583,'Course','created','App\\Models\\Institution\\Course','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Purchasing & Supply Management\", \"position\": 27, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(584,'Course','created','App\\Models\\Institution\\Course','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Sales & Marketing Management\", \"position\": 28, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(585,'Course','created','App\\Models\\Institution\\Course','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Trainers Diploma In Education\", \"position\": 29, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(586,'Course','created','App\\Models\\Institution\\Course','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Transport & Logistics Management\", \"position\": 30, \"description\": \"Commerce\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(587,'Course','created','App\\Models\\Institution\\Course','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Architectural Technology\", \"position\": 31, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(588,'Course','created','App\\Models\\Institution\\Course','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Cartography & Geo-Visualization Theory Technology\", \"position\": 32, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(589,'Course','created','App\\Models\\Institution\\Course','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"Civil Engineering\", \"position\": 33, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(590,'Course','created','App\\Models\\Institution\\Course','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"Quantity Surveying\", \"position\": 34, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(591,'Course','created','App\\Models\\Institution\\Course','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"Surveying and Geomatics\", \"position\": 35, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(592,'Course','created','App\\Models\\Institution\\Course','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"Urban And Regional Planning\", \"position\": 36, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(593,'Course','created','App\\Models\\Institution\\Course','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"Valuation & Estate Management\", \"position\": 37, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(594,'Course','created','App\\Models\\Institution\\Course','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"Water Resources & Irrigation Engineering\", \"position\": 38, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(595,'Course','created','App\\Models\\Institution\\Course','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"Building Technology\", \"position\": 39, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(596,'Course','created','App\\Models\\Institution\\Course','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"Carpentry and Joinery\", \"position\": 40, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(597,'Course','created','App\\Models\\Institution\\Course','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"Construction Engineering\", \"position\": 41, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(598,'Course','created','App\\Models\\Institution\\Course','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"Painting and Decorating Technology\", \"position\": 42, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(599,'Course','created','App\\Models\\Institution\\Course','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"Plumbing and Drain Laying\", \"position\": 43, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(600,'Course','created','App\\Models\\Institution\\Course','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"Computer Systems\", \"position\": 44, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(601,'Course','created','App\\Models\\Institution\\Course','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"Domestic and Industrial Solar Installation\", \"position\": 45, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(602,'Course','created','App\\Models\\Institution\\Course','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"Electrical Power Engineering\", \"position\": 46, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(603,'Course','created','App\\Models\\Institution\\Course','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"Electronic Communication Systems\", \"position\": 47, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(604,'Course','created','App\\Models\\Institution\\Course','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"Instrumentation and Control Systems\", \"position\": 48, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(605,'Course','created','App\\Models\\Institution\\Course','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"Microwave and Radar\", \"position\": 49, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(606,'Course','created','App\\Models\\Institution\\Course','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"Mobile and Satellite Communication\", \"position\": 50, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(607,'Course','created','App\\Models\\Institution\\Course','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"Information Technology\", \"position\": 51, \"description\": \"Information Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(608,'Course','created','App\\Models\\Institution\\Course','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Computer Engineering\", \"position\": 52, \"description\": \"Information Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(609,'Course','created','App\\Models\\Institution\\Course','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Computing and Information Systems\", \"position\": 53, \"description\": \"Information Technology\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(610,'Course','created','App\\Models\\Institution\\Course','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"Library and Information Sciences\", \"position\": 54, \"description\": \"Library and Information Systems\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(611,'Course','created','App\\Models\\Institution\\Course','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"Records Management and Information Sciences\", \"position\": 55, \"description\": \"Library and Information Systems\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(612,'Course','created','App\\Models\\Institution\\Course','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"Draughting and Design Technology\", \"position\": 56, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(613,'Course','created','App\\Models\\Institution\\Course','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"Fabrication Engineering\", \"position\": 57, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(614,'Course','created','App\\Models\\Institution\\Course','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"Machine Shop Engineering\", \"position\": 58, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(615,'Course','created','App\\Models\\Institution\\Course','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"Mechanical Engineering\", \"position\": 59, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(616,'Course','created','App\\Models\\Institution\\Course','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"Millwright Works\", \"position\": 60, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(617,'Course','created','App\\Models\\Institution\\Course','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"Plant Engineering\", \"position\": 61, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(618,'Course','created','App\\Models\\Institution\\Course','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"Production Engineering\", \"position\": 62, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(619,'Course','created','App\\Models\\Institution\\Course','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"Refrigeration and Air Conditioning\", \"position\": 63, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(620,'Course','created','App\\Models\\Institution\\Course','created',64,NULL,NULL,'{\"attributes\": {\"name\": \"Vehicle Body Building\", \"position\": 64, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(621,'Course','created','App\\Models\\Institution\\Course','created',65,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Art and Design\", \"position\": 65, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(622,'Course','created','App\\Models\\Institution\\Course','created',66,NULL,NULL,'{\"attributes\": {\"name\": \"Design For Print\", \"position\": 66, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(623,'Course','created','App\\Models\\Institution\\Course','created',67,NULL,NULL,'{\"attributes\": {\"name\": \"Fine Arts\", \"position\": 67, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(624,'Course','created','App\\Models\\Institution\\Course','created',68,NULL,NULL,'{\"attributes\": {\"name\": \"Machine Printing\", \"position\": 68, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(625,'Course','created','App\\Models\\Institution\\Course','created',69,NULL,NULL,'{\"attributes\": {\"name\": \"Multimedia\", \"position\": 69, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(626,'Course','created','App\\Models\\Institution\\Course','created',70,NULL,NULL,'{\"attributes\": {\"name\": \"Packaging Machine Minding\", \"position\": 70, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(627,'Course','created','App\\Models\\Institution\\Course','created',71,NULL,NULL,'{\"attributes\": {\"name\": \"Photography\", \"position\": 71, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(628,'Course','created','App\\Models\\Institution\\Course','created',72,NULL,NULL,'{\"attributes\": {\"name\": \"Printing, Finishing and Converting\", \"position\": 72, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(629,'Course','created','App\\Models\\Institution\\Course','created',73,NULL,NULL,'{\"attributes\": {\"name\": \"Print Finishing Technology\", \"position\": 73, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(630,'Course','created','App\\Models\\Institution\\Course','created',74,NULL,NULL,'{\"attributes\": {\"name\": \"Print Production Technology\", \"position\": 74, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(631,'Course','created','App\\Models\\Institution\\Course','created',75,NULL,NULL,'{\"attributes\": {\"name\": \"Print Origination Technology\", \"position\": 75, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(632,'Course','created','App\\Models\\Institution\\Course','created',76,NULL,NULL,'{\"attributes\": {\"name\": \"Broadcast Journalism\", \"position\": 76, \"description\": \"Mass Communication\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(633,'Course','created','App\\Models\\Institution\\Course','created',77,NULL,NULL,'{\"attributes\": {\"name\": \"Mass Communication\", \"position\": 77, \"description\": \"Mass Communication\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(634,'Course','created','App\\Models\\Institution\\Course','created',78,NULL,NULL,'{\"attributes\": {\"name\": \"Print Journalism\", \"position\": 78, \"description\": \"Mass Communication\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(635,'Course','created','App\\Models\\Institution\\Course','created',79,NULL,NULL,'{\"attributes\": {\"name\": \"Public Relations\", \"position\": 79, \"description\": \"Mass Communication\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(636,'Course','created','App\\Models\\Institution\\Course','created',80,NULL,NULL,'{\"attributes\": {\"name\": \"Office Management\", \"position\": 80, \"description\": \"Office Management\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(637,'Course','created','App\\Models\\Institution\\Course','created',81,NULL,NULL,'{\"attributes\": {\"name\": \"Bakery Technology and Management\", \"position\": 81, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(638,'Course','created','App\\Models\\Institution\\Course','created',82,NULL,NULL,'{\"attributes\": {\"name\": \"Culinary Arts\", \"position\": 82, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(639,'Course','created','App\\Models\\Institution\\Course','created',83,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Cookery\", \"position\": 83, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(640,'Course','created','App\\Models\\Institution\\Course','created',84,NULL,NULL,'{\"attributes\": {\"name\": \"Tourism and Hospitality Management\", \"position\": 84, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(641,'Department','created','App\\Models\\Institution\\Department','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Arts\", \"position\": 1, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(642,'Department','created','App\\Models\\Institution\\Department','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Engineering\", \"position\": 2, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(643,'Department','created','App\\Models\\Institution\\Department','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Business & Management Studies\", \"position\": 3, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(644,'Department','created','App\\Models\\Institution\\Department','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Civil Engineering\", \"position\": 4, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(645,'Department','created','App\\Models\\Institution\\Department','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Construction Engineering\", \"position\": 5, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(646,'Department','created','App\\Models\\Institution\\Department','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Electrical Engineering\", \"position\": 6, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(647,'Department','created','App\\Models\\Institution\\Department','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Information Communication Technology\", \"position\": 7, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(648,'Department','created','App\\Models\\Institution\\Department','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Library & Info Sciences\", \"position\": 8, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(649,'Department','created','App\\Models\\Institution\\Department','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Mass Communication\", \"position\": 9, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(650,'Department','created','App\\Models\\Institution\\Department','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Mechanical & Production Engineering\", \"position\": 10, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(651,'Department','created','App\\Models\\Institution\\Department','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Office Management\", \"position\": 11, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(652,'Department','created','App\\Models\\Institution\\Department','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Printing And Graphics Arts\", \"position\": 12, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(653,'Department','created','App\\Models\\Institution\\Department','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Science Technology\", \"position\": 13, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(654,'Department','created','App\\Models\\Institution\\Department','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Tourism And Hospitality\", \"position\": 14, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(655,'Division','created','App\\Models\\Institution\\Division','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Business\", \"position\": 1, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(656,'Division','created','App\\Models\\Institution\\Division','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Management\", \"position\": 2, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(657,'Division','created','App\\Models\\Institution\\Division','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Pedagogics\", \"position\": 3, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(658,'Grade','created','App\\Models\\Institution\\Grade','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"A\", \"position\": 1, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(659,'Grade','created','App\\Models\\Institution\\Grade','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"B\", \"position\": 2, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(660,'Grade','created','App\\Models\\Institution\\Grade','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"C\", \"position\": 3, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(661,'Grade','created','App\\Models\\Institution\\Grade','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"D\", \"position\": 4, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(662,'Grade','created','App\\Models\\Institution\\Grade','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"E\", \"position\": 5, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(663,'Grade','created','App\\Models\\Institution\\Grade','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"U\", \"position\": 6, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(664,'Level','created','App\\Models\\Institution\\Level','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 3\", \"position\": 1, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(665,'Level','created','App\\Models\\Institution\\Level','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 4\", \"position\": 2, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(666,'Level','created','App\\Models\\Institution\\Level','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 5\", \"position\": 3, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(667,'Level','created','App\\Models\\Institution\\Level','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 6\", \"position\": 4, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(668,'Level','created','App\\Models\\Institution\\Level','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"NC\", \"position\": 5, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(669,'Level','created','App\\Models\\Institution\\Level','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"ND\", \"position\": 6, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(670,'Level','created','App\\Models\\Institution\\Level','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"HND\", \"position\": 7, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(671,'Level','created','App\\Models\\Institution\\Level','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"BTECH\", \"position\": 8, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(672,'Level','created','App\\Models\\Institution\\Level','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"SDP\", \"position\": 9, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(673,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Primary school\", \"position\": 1, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(674,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Secondary school\", \"position\": 2, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(675,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Advanced Level\", \"position\": 3, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(676,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Tertiary Level\", \"position\": 4, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(677,'Relationship','created','App\\Models\\Shared\\Relationship','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Parent\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(678,'Relationship','created','App\\Models\\Shared\\Relationship','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Spouse\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(679,'Relationship','created','App\\Models\\Shared\\Relationship','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Guardian\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(680,'Subject','created','App\\Models\\Institution\\Subject','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Accounts\", \"position\": 1, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(681,'Subject','created','App\\Models\\Institution\\Subject','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Agriculture\", \"position\": 2, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(682,'Subject','created','App\\Models\\Institution\\Subject','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Art\", \"position\": 3, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(683,'Subject','created','App\\Models\\Institution\\Subject','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Bible Knowledge\", \"position\": 4, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(684,'Subject','created','App\\Models\\Institution\\Subject','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Building Studies\", \"position\": 5, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(685,'Subject','created','App\\Models\\Institution\\Subject','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Business and Enterprise Skills\", \"position\": 6, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(686,'Subject','created','App\\Models\\Institution\\Subject','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Business Studies\", \"position\": 7, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(687,'Subject','created','App\\Models\\Institution\\Subject','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Chinese\", \"position\": 8, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(688,'Subject','created','App\\Models\\Institution\\Subject','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Commerce\", \"position\": 9, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(689,'Subject','created','App\\Models\\Institution\\Subject','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Computer Science\", \"position\": 10, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(690,'Subject','created','App\\Models\\Institution\\Subject','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Design and Technology\", \"position\": 11, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(691,'Subject','created','App\\Models\\Institution\\Subject','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Economics\", \"position\": 12, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(692,'Subject','created','App\\Models\\Institution\\Subject','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"English\", \"position\": 13, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(693,'Subject','created','App\\Models\\Institution\\Subject','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Fashion and Fabrics\", \"position\": 14, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(694,'Subject','created','App\\Models\\Institution\\Subject','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Food and Nutrition\", \"position\": 15, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(695,'Subject','created','App\\Models\\Institution\\Subject','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"French\", \"position\": 16, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(696,'Subject','created','App\\Models\\Institution\\Subject','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Geography\", \"position\": 17, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(697,'Subject','created','App\\Models\\Institution\\Subject','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"German\", \"position\": 18, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(698,'Subject','created','App\\Models\\Institution\\Subject','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"History\", \"position\": 19, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(699,'Subject','created','App\\Models\\Institution\\Subject','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Integrated Science\", \"position\": 20, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(700,'Subject','created','App\\Models\\Institution\\Subject','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Literature in English\", \"position\": 21, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(701,'Subject','created','App\\Models\\Institution\\Subject','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Mathematics\", \"position\": 22, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(702,'Subject','created','App\\Models\\Institution\\Subject','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Metal Technology and Design\", \"position\": 23, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(703,'Subject','created','App\\Models\\Institution\\Subject','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Music\", \"position\": 24, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(704,'Subject','created','App\\Models\\Institution\\Subject','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Ndebele\", \"position\": 25, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(705,'Subject','created','App\\Models\\Institution\\Subject','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Physical Education, Sport and Mass Displays\", \"position\": 26, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(706,'Subject','created','App\\Models\\Institution\\Subject','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Religious Studies\", \"position\": 27, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(707,'Subject','created','App\\Models\\Institution\\Subject','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Shona\", \"position\": 28, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(708,'Subject','created','App\\Models\\Institution\\Subject','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Spanish\", \"position\": 29, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(709,'Subject','created','App\\Models\\Institution\\Subject','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Technical Graphics\", \"position\": 30, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(710,'Subject','created','App\\Models\\Institution\\Subject','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Wood Technology and Design\", \"position\": 31, \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(711,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Full Time\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(712,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Part Time\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(713,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Block Release\", \"description\": null}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(714,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',1,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 1}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(715,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',2,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 2}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(716,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',3,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 3}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(717,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',4,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 4}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(718,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',5,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 5}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(719,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',6,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 6}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(720,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',7,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 7}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(721,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',8,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 8}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(722,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',9,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 9}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(723,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',10,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 10}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(724,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',11,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 11}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(725,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',12,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 12}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(726,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',13,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 13}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(727,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',14,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 14}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(728,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Full time\", \"description\": \"Full-time employment (35–40+ hours/week with benefits)\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(729,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Part time\", \"description\": \"Part-time employment (less than 35 hours/week)\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(730,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Temporary\", \"description\": \"Temporary or contract-based employment\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(731,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Freelance\", \"description\": \"Freelance or self-employed contractor work\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(732,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Intern\", \"description\": \"Internship or apprenticeship (temporary, for experience)\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(733,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Casual\", \"description\": \"Casual work (on-call or irregular hours)\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(734,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Seasonal\", \"description\": \"Seasonal employment (e.g. holiday or harvest periods)\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01'),(735,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Remote\", \"description\": \"Remote or telecommuting work (offsite)\"}}',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;

--
-- Table structure for table `address_types`
--

DROP TABLE IF EXISTS `address_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_types_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_types`
--

/*!40000 ALTER TABLE `address_types` DISABLE KEYS */;
INSERT INTO `address_types` VALUES (1,'Business','business',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Complex','complex',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Home','home',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'Physical','physical',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'Postal','postal',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `address_types` ENABLE KEYS */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `addressable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `addressable_id` bigint unsigned NOT NULL,
  `address_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_6` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_is_main` tinyint(1) NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_addressable_type_addressable_id_index` (`addressable_type`,`addressable_id`),
  KEY `addresses_tenant_id_index` (`tenant_id`),
  CONSTRAINT `addresses_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;

--
-- Table structure for table `application_steps`
--

DROP TABLE IF EXISTS `application_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `position` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_steps_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_steps`
--

/*!40000 ALTER TABLE `application_steps` DISABLE KEYS */;
INSERT INTO `application_steps` VALUES (1,'Draft / Incomplete','Application started but not completed',1,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(2,'Submitted','Application has been submitted for review',2,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(3,'In Review','Application is currently being reviewed',3,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(4,'Awaiting Requirements','Waiting for required documents or information',4,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(5,'Awaiting Payment','Waiting for application fee payment',5,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(6,'Interview Scheduled','Interview has been scheduled',6,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(7,'Interview Completed','Interview has been completed',7,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(8,'Decision Pending','Final decision is being prepared',8,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(9,'Accepted / Offer Made','Offer of acceptance has been made',9,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(10,'Waitlisted','Application is on the waitlist',10,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(11,'Rejected','Application was not successful',11,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(12,'Offer Accepted','Offer has been accepted by the applicant',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(13,'Offer Declined','Offer has been declined by the applicant',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(14,'Enrolled / Registered','Applicant has enrolled and registered successfully',14,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `application_steps` ENABLE KEYS */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;

--
-- Table structure for table `communication_methods`
--

DROP TABLE IF EXISTS `communication_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_methods_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_methods`
--

/*!40000 ALTER TABLE `communication_methods` DISABLE KEYS */;
INSERT INTO `communication_methods` VALUES (1,'Email','2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Sms','2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Phone','2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `communication_methods` ENABLE KEYS */;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `contactable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contactable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_email_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_is_main` tinyint(1) NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_contactable_type_contactable_id_index` (`contactable_type`,`contactable_id`),
  KEY `contacts_tenant_id_index` (`tenant_id`),
  CONSTRAINT `contacts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Afghanistan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Albania',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Algeria',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'Andorra',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'Angola',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(6,'Antigua and Barbuda',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(7,'Argentina',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(8,'Armenia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(9,'Australia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(10,'Azerbaijan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(11,'Bahamas',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(12,'Bahrain',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(13,'Bangladesh',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(14,'Barbados',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(15,'Belgium',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(16,'Belize',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(17,'Benin',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(18,'Bhutan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(19,'Bolivia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(20,'Bosnia and Herzegovina',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(21,'Botswana',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(22,'Brazil',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(23,'Brunei',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(24,'Bulgaria',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(25,'Burkina Faso',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(26,'Burundi',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(27,'Chile',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(28,'Colombia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(29,'Comoros',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(30,'Costa Rica',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(31,'Democratic Republic of the Congo',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(32,'Denmark',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(33,'Djibouti',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(34,'Dominica',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(35,'Dominican Republic',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(36,'Germany',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(37,'Ecuador',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(38,'Egypt',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(39,'Equatorial Guinea',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(40,'El Salvador',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(41,'Eritrea',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(42,'Estonia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(43,'Ethiopia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(44,'Fiji',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(45,'Philippines',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(46,'Finland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(47,'France',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(48,'Gabon',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(49,'Gambia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(50,'Georgia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(51,'Ghana',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(52,'Grenada',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(53,'Greece',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(54,'Guatemala',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(55,'Guinea',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(56,'Guinea-Bissau',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(57,'Guyana',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(58,'Haiti',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(59,'Honduras',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(60,'Hungary',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(61,'Ireland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(62,'India',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(63,'Indonesia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(64,'Iran',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(65,'Iraq',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(66,'Israel',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(67,'Italy',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(68,'Côte d’Ivoire',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(69,'Jamaica',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(70,'Japan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(71,'Yemen',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(72,'Jordan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(73,'Cape Verde Islands',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(74,'Cambodia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(75,'Cameroon',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(76,'Canada',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(77,'Kazakhstan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(78,'Qatar',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(79,'Kenya',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(80,'Kyrgyzstan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(81,'Kiribati',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(82,'Kuwait',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(83,'Croatia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(84,'Cuba',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(85,'Laos',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(86,'Latvia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(87,'Lesotho',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(88,'Lebanon',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(89,'Liberia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(90,'Libya',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(91,'Liechtenstein',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(92,'Lithuania',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(93,'Luxembourg',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(94,'Madagascar',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(95,'Malawi',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(96,'Malaysia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(97,'Maldives',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(98,'Mali',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(99,'Malta',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(100,'Morocco',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(101,'Marshall Islands',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(102,'Mauritania',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(103,'Mauritius',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(104,'Mexico',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(105,'Myanmar (Burma)',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(106,'Micronesia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(107,'Moldova',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(108,'Monaco',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(109,'Mongolia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(110,'Montenegro',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(111,'Mozambique',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(112,'Namibia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(113,'Nauru',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(114,'Nepal',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(115,'Netherlands',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(116,'New Zealand',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(117,'Nicaragua',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(118,'Niger',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(119,'Nigeria',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(120,'North Korea',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(121,'Norway',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(122,'Ukraine',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(123,'Uzbekistan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(124,'Oman',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(125,'Austria',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(126,'East Timor',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(127,'Pakistan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(128,'Palau',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(129,'Panama',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(130,'Papua New Guinea',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(131,'Paraguay',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(132,'Peru',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(133,'Pole/Poland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(134,'Portugal',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(135,'Republic of the Congo',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(136,'Macedonia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(137,'Romania',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(138,'Russia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(139,'Rwanda',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(140,'Samoa',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(141,'San Marino',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(142,'Saudi Arabia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(143,'São Tomé and Principe',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(144,'Senegal',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(145,'Central African Republic',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(146,'Serbia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(147,'Seychelles',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(148,'China',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(149,'Sierra Leone',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(150,'Singapore',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(151,'Cyprus',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(152,'Syria',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(153,'Slovakia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(154,'Slovenia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(155,'Sudan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(156,'Solomon Islands',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(157,'Somalia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(158,'Spain',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(159,'Sri Lanka',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(160,'Saint Kitts and Nevis',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(161,'St. Lucia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(162,'St. Vincent and the Grenadines',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(163,'South Africa',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(164,'Suid-Korea/South Korea',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(165,'South Sudan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(166,'Suriname',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(167,'Swaziland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(168,'Sweden',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(169,'Switzerland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(170,'Tajikistan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(171,'Taiwan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(172,'Tanzania',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(173,'Thailand',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(174,'Tonga',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(175,'Togo',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(176,'Trinidad and Tobago',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(177,'Chad',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(178,'Czech Republic',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(179,'Tunisia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(180,'Turkmenistan',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(181,'Turkey',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(182,'Tuvalu',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(183,'Uganda',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(184,'Uruguay',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(185,'Vanuatu',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(186,'Venezuela',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(187,'United Arab Emirates',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(188,'United Kingdom',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(189,'United States of America',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(190,'Vietnam',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(191,'Belarus',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(192,'Iceland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(193,'Zambia',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(194,'Zimbabwe',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'Beauty Therapy',1,'Applied Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Cosmetology',2,'Applied Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Hairdressing',3,'Applied Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'Industrial Clothing Design and Construction Design',4,'Applied Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'Applied Biological Technology',5,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,'Applied Chemical Technology',6,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(7,'Chemical Engineering',7,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(8,'Chemical Technology',8,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(9,'Food Science',9,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(10,'Horticulture',10,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(11,'Laboratory Technology',11,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(12,'Metallurgical Assaying',12,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(13,'Pharmaceutical Technology',13,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(14,'Polymer Technology',14,'Applied Science Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(15,'Automobile Electrics And Electronics',15,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(16,'Automotive Engineering',16,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(17,'Automotive Precision Machining',17,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(18,'Diesel Plant Fitting',18,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(19,'Motor Cycle Machining',19,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(20,'Motor Vehicle Body Repairs',20,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(21,'Motor Vehicle Mechanics',21,'Automotive','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(22,'Accountancy',22,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(23,'Banking and Finance',23,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(24,'Health Services Management',24,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(25,'Human Resources Management',25,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(26,'Pensions & Investments Management',26,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(27,'Purchasing & Supply Management',27,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(28,'Sales & Marketing Management',28,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(29,'Trainers Diploma In Education',29,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(30,'Transport & Logistics Management',30,'Commerce','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(31,'Architectural Technology',31,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(32,'Cartography & Geo-Visualization Theory Technology',32,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(33,'Civil Engineering',33,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(34,'Quantity Surveying',34,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(35,'Surveying and Geomatics',35,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(36,'Urban And Regional Planning',36,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(37,'Valuation & Estate Management',37,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(38,'Water Resources & Irrigation Engineering',38,'Civil Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(39,'Building Technology',39,'Construction Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(40,'Carpentry and Joinery',40,'Construction Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(41,'Construction Engineering',41,'Construction Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(42,'Painting and Decorating Technology',42,'Construction Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(43,'Plumbing and Drain Laying',43,'Construction Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(44,'Computer Systems',44,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(45,'Domestic and Industrial Solar Installation',45,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(46,'Electrical Power Engineering',46,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(47,'Electronic Communication Systems',47,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(48,'Instrumentation and Control Systems',48,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(49,'Microwave and Radar',49,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(50,'Mobile and Satellite Communication',50,'Electrical Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(51,'Information Technology',51,'Information Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(52,'Professional Computer Engineering',52,'Information Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(53,'Professional Computing and Information Systems',53,'Information Technology','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(54,'Library and Information Sciences',54,'Library and Information Systems','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(55,'Records Management and Information Sciences',55,'Library and Information Systems','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(56,'Draughting and Design Technology',56,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(57,'Fabrication Engineering',57,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(58,'Machine Shop Engineering',58,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(59,'Mechanical Engineering',59,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(60,'Millwright Works',60,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(61,'Plant Engineering',61,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(62,'Production Engineering',62,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(63,'Refrigeration and Air Conditioning',63,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(64,'Vehicle Body Building',64,'Mechanical and Production Engineering','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(65,'Applied Art and Design',65,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(66,'Design For Print',66,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(67,'Fine Arts',67,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(68,'Machine Printing',68,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(69,'Multimedia',69,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(70,'Packaging Machine Minding',70,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(71,'Photography',71,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(72,'Printing, Finishing and Converting',72,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(73,'Print Finishing Technology',73,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(74,'Print Production Technology',74,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(75,'Print Origination Technology',75,'Printing and Graphic Arts','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(76,'Broadcast Journalism',76,'Mass Communication','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(77,'Mass Communication',77,'Mass Communication','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(78,'Print Journalism',78,'Mass Communication','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(79,'Public Relations',79,'Mass Communication','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(80,'Office Management',80,'Office Management','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(81,'Bakery Technology and Management',81,'Tourism and Hospitality','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(82,'Culinary Arts',82,'Tourism and Hospitality','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(83,'Professional Cookery',83,'Tourism and Hospitality','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(84,'Tourism and Hospitality Management',84,'Tourism and Hospitality','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;

--
-- Table structure for table `department_courses`
--

DROP TABLE IF EXISTS `department_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `institution_department_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `show_on_current_application_period` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_courses_institution_department_id_foreign` (`institution_department_id`),
  KEY `department_courses_course_id_foreign` (`course_id`),
  KEY `department_courses_tenant_id_index` (`tenant_id`),
  CONSTRAINT `department_courses_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  CONSTRAINT `department_courses_institution_department_id_foreign` FOREIGN KEY (`institution_department_id`) REFERENCES `institution_departments` (`id`),
  CONSTRAINT `department_courses_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_courses`
--

/*!40000 ALTER TABLE `department_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_courses` ENABLE KEYS */;

--
-- Table structure for table `department_level_courses`
--

DROP TABLE IF EXISTS `department_level_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_level_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_course_id` bigint unsigned NOT NULL,
  `department_level_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_level_courses`
--

/*!40000 ALTER TABLE `department_level_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_level_courses` ENABLE KEYS */;

--
-- Table structure for table `department_level_requirements`
--

DROP TABLE IF EXISTS `department_level_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_level_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `department_level_id` bigint unsigned NOT NULL,
  `is_o_level_required` tinyint(1) NOT NULL,
  `required_subjects_count` int DEFAULT NULL,
  `main_subjects_count` int DEFAULT NULL,
  `main_subject_ids` json DEFAULT NULL,
  `other_subjects_count` int DEFAULT NULL,
  `only_read_write_required` tinyint(1) NOT NULL,
  `required_level_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_level_requirements_tenant_id_index` (`tenant_id`),
  KEY `department_level_requirements_department_level_id_index` (`department_level_id`),
  CONSTRAINT `department_level_requirements_department_level_id_foreign` FOREIGN KEY (`department_level_id`) REFERENCES `department_levels` (`id`),
  CONSTRAINT `department_level_requirements_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_level_requirements`
--

/*!40000 ALTER TABLE `department_level_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_level_requirements` ENABLE KEYS */;

--
-- Table structure for table `department_levels`
--

DROP TABLE IF EXISTS `department_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `institution_department_id` bigint unsigned NOT NULL,
  `level_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_levels_institution_department_id_foreign` (`institution_department_id`),
  KEY `department_levels_level_id_foreign` (`level_id`),
  KEY `department_levels_tenant_id_index` (`tenant_id`),
  CONSTRAINT `department_levels_institution_department_id_foreign` FOREIGN KEY (`institution_department_id`) REFERENCES `institution_departments` (`id`),
  CONSTRAINT `department_levels_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`),
  CONSTRAINT `department_levels_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_levels`
--

/*!40000 ALTER TABLE `department_levels` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_levels` ENABLE KEYS */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Applied Arts',1,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Automotive Engineering',2,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Business & Management Studies',3,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'Civil Engineering',4,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'Construction Engineering',5,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,'Electrical Engineering',6,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(7,'Information Communication Technology',7,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(8,'Library & Info Sciences',8,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(9,'Mass Communication',9,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(10,'Mechanical & Production Engineering',10,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(11,'Office Management',11,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(12,'Printing And Graphics Arts',12,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(13,'Science Technology',13,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(14,'Tourism And Hospitality',14,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `districts_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districts`
--

/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` VALUES (1,'Bulawayo',1,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Chitungwiza',2,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Harare',2,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'Buhera',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'Chimanimani',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(6,'Chipinge',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(7,'Makoni',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(8,'Mutare',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(9,'Mutasa',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(10,'Nyanga',3,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(11,'Bindura',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(12,'Guruve',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(13,'Mazowe',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(14,'Mbire',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(15,'Mount Darwin',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(16,'Muzarabani',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(17,'Rushinga',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(18,'Shamva',4,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(19,'Chikomba',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(20,'Goromonzi',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(21,'Marondera',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(22,'Mudzi',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(23,'Murehwa',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(24,'Mutoko',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(25,'Seke',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(26,'UMP (Uzumba-Maramba-Pfungwe)',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(27,'Wedza (Hwedza)',5,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(28,'Chegutu',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(29,'Hurungwe',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(30,'Kariba',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(31,'Makonde',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(32,'Mhondoro-Ngezi',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(33,'Sanyati',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(34,'Zvimba',6,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(35,'Bikita',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(36,'Chiredzi',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(37,'Chivi',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(38,'Gutu',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(39,'Masvingo',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(40,'Mwenezi',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(41,'Zaka',7,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(42,'Binga',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(43,'Bubi',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(44,'Hwange',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(45,'Lupane',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(46,'Nkayi',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(47,'Tsholotsho',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(48,'Umguza',8,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(49,'Beitbridge',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(50,'Bulilima',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(51,'Gwanda',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(52,'Insiza',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(53,'Mangwe',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(54,'Matobo',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(55,'Umzingwane',9,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(56,'Chirumhanzu',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(57,'Gokwe North',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(58,'Gokwe South',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(59,'Gweru',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(60,'Kwekwe',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(61,'Mberengwa',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(62,'Shurugwi',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(63,'Zvishavane',10,NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;

--
-- Table structure for table `divisions`
--

DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `divisions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `divisions`
--

/*!40000 ALTER TABLE `divisions` DISABLE KEYS */;
INSERT INTO `divisions` VALUES (1,'Business',1,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Management',2,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Pedagogics',3,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `divisions` ENABLE KEYS */;

--
-- Table structure for table `employment_types`
--

DROP TABLE IF EXISTS `employment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employment_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_types`
--

/*!40000 ALTER TABLE `employment_types` DISABLE KEYS */;
INSERT INTO `employment_types` VALUES (1,'Full time','Full-time employment (35–40+ hours/week with benefits)','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Part time','Part-time employment (less than 35 hours/week)','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Temporary','Temporary or contract-based employment','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'Freelance','Freelance or self-employed contractor work','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'Intern','Internship or apprenticeship (temporary, for experience)','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,'Casual','Casual work (on-call or irregular hours)','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(7,'Seasonal','Seasonal employment (e.g. holiday or harvest periods)','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(8,'Remote','Remote or telecommuting work (offsite)','2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `employment_types` ENABLE KEYS */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;

--
-- Table structure for table `genders`
--

DROP TABLE IF EXISTS `genders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `genders_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genders`
--

/*!40000 ALTER TABLE `genders` DISABLE KEYS */;
INSERT INTO `genders` VALUES (1,'Male',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(2,'Female',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `genders` ENABLE KEYS */;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grades_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
INSERT INTO `grades` VALUES (1,'A',1,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'B',2,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'C',3,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'D',4,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'E',5,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,'U',6,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;

--
-- Table structure for table `institution_department_staff`
--

DROP TABLE IF EXISTS `institution_department_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institution_department_staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned NOT NULL,
  `institution_department_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_department_unique` (`staff_id`,`institution_department_id`),
  KEY `institution_department_staff_institution_department_id_foreign` (`institution_department_id`),
  CONSTRAINT `institution_department_staff_institution_department_id_foreign` FOREIGN KEY (`institution_department_id`) REFERENCES `institution_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `institution_department_staff_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institution_department_staff`
--

/*!40000 ALTER TABLE `institution_department_staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `institution_department_staff` ENABLE KEYS */;

--
-- Table structure for table `institution_departments`
--

DROP TABLE IF EXISTS `institution_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institution_departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_departments_department_id_foreign` (`department_id`),
  KEY `institution_departments_tenant_id_index` (`tenant_id`),
  CONSTRAINT `institution_departments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `institution_departments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institution_departments`
--

/*!40000 ALTER TABLE `institution_departments` DISABLE KEYS */;
INSERT INTO `institution_departments` VALUES (1,1,1,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,1,2,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,1,3,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,1,4,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,1,5,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,1,6,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(7,1,7,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(8,1,8,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(9,1,9,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(10,1,10,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(11,1,11,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(12,1,12,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(13,1,13,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(14,1,14,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `institution_departments` ENABLE KEYS */;

--
-- Table structure for table `intake_periods`
--

DROP TABLE IF EXISTS `intake_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intake_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `intake_periods_tenant_id_index` (`tenant_id`),
  CONSTRAINT `intake_periods_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intake_periods`
--

/*!40000 ALTER TABLE `intake_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `intake_periods` ENABLE KEYS */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `languages_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,'English',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;

--
-- Table structure for table `levels`
--

DROP TABLE IF EXISTS `levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `levels_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `levels`
--

/*!40000 ALTER TABLE `levels` DISABLE KEYS */;
INSERT INTO `levels` VALUES (1,'ABMA Level 3',1,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'ABMA Level 4',2,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'ABMA Level 5',3,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'ABMA Level 6',4,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'NC',5,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,'ND',6,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(7,'HND',7,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(8,'BTECH',8,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(9,'SDP',9,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `levels` ENABLE KEYS */;

--
-- Table structure for table `marital_statuses`
--

DROP TABLE IF EXISTS `marital_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marital_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marital_statuses_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marital_statuses`
--

/*!40000 ALTER TABLE `marital_statuses` DISABLE KEYS */;
INSERT INTO `marital_statuses` VALUES (1,'Divorced',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Engaged',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Married',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'Single',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'Widowed',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `marital_statuses` ENABLE KEYS */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_create_tenants_table',1),(2,'0001_00_19_183342_create_races_table',1),(3,'0001_01_00_000000_create_statuses_table',1),(4,'0001_01_00_125713_create_genders_table',1),(5,'0001_01_00_183404_create_titles_table',1),(6,'0001_01_01_000000_create_users_table',1),(7,'0001_01_01_000001_create_cache_table',1),(8,'0001_01_01_000002_create_jobs_table',1),(9,'2024_12_10_073103_create_media_table',1),(10,'2024_12_10_073229_create_activity_log_table',1),(11,'2024_12_10_073230_add_event_column_to_activity_log_table',1),(12,'2024_12_10_073231_add_batch_uuid_column_to_activity_log_table',1),(13,'2024_12_10_091905_create_modules_table',1),(14,'2024_12_10_112501_create_permission_tables',1),(15,'2025_01_18_202508_create_communication_methods_table',1),(16,'2025_01_18_222026_create_countries_table',1),(17,'2025_01_19_101127_create_payment_days_table',1),(18,'2025_01_19_101323_create_payment_frequencies_table',1),(19,'2025_01_19_101437_create_payment_methods_table',1),(20,'2025_01_19_140446_create_languages_table',1),(21,'2025_01_19_143527_create_provinces_table',1),(22,'2025_03_20_185152_create_addresses_table',1),(23,'2025_03_20_190050_create_contacts_table',1),(24,'2025_03_22_053137_create_address_types_table',1),(25,'2025_04_25_173642_create_departments_table',1),(26,'2025_04_25_173916_create_courses_table',1),(27,'2025_04_25_174007_create_divisions_table',1),(28,'2025_04_25_174046_create_grades_table',1),(29,'2025_04_25_174107_create_levels_table',1),(30,'2025_04_25_174151_create_relationships_table',1),(31,'2025_04_25_174216_create_subjects_table',1),(32,'2025_04_25_193714_create_mode_of_studies_table',1),(33,'2025_04_27_142505_create_districts_table',1),(34,'2025_04_28_135636_create_institution_departments_table',1),(35,'2025_05_06_231759_create_department_levels_table',1),(36,'2025_05_07_152341_create_personal_access_tokens_table',1),(37,'2025_05_09_073840_create_department_courses_table',1),(38,'2025_05_13_164228_create_department_course_levels_table',1),(39,'2025_05_22_063933_create_department_level_requirements_table',1),(40,'2025_05_26_082810_create_marital_statuses_table',1),(41,'2025_06_19_045841_create_students_table',1),(42,'2025_06_19_053738_create_student_programs_table',1),(43,'2025_06_20_012032_create_next_of_kin_table',1),(44,'2025_06_21_115803_create_religions_table',1),(45,'2025_06_23_054353_create_academic_levels_table',1),(46,'2025_06_23_125237_create_sponsors_table',1),(47,'2025_06_23_132119_create_sponsor_types_table',1),(48,'2025_06_26_034105_create_academic_records_table',1),(49,'2025_06_29_085659_create_application_steps_table',1),(50,'2025_06_30_125235_create_intake_periods_table',1),(51,'2025_07_00_195358_create_employment_types_table',1),(52,'2025_07_02_052540_create_staff_table',1),(53,'2025_07_03_135229_create_institution_department_staff_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;

--
-- Table structure for table `mode_of_studies`
--

DROP TABLE IF EXISTS `mode_of_studies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mode_of_studies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mode_of_studies_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mode_of_studies`
--

/*!40000 ALTER TABLE `mode_of_studies` DISABLE KEYS */;
INSERT INTO `mode_of_studies` VALUES (1,'Full Time',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Part Time',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Block Release',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `mode_of_studies` ENABLE KEYS */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\Users\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'Accommodations','accommodations',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(2,'Acl','acl',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(3,'Communications','communications',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(4,'Dashboards','dashboards',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(5,'Enrolments','enrolments',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(6,'Examinations','examinations',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(7,'Institution','institution',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(8,'Other','other',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(9,'Reports','reports',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(10,'Root','root',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(11,'Settings','settings',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(12,'Shared','shared',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(13,'Students','students',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(14,'Tenants','tenants',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(15,'Users','users',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL);
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;

--
-- Table structure for table `next_of_kin`
--

DROP TABLE IF EXISTS `next_of_kin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `next_of_kin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `kinnable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kinnable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `next_of_kin_kinnable_type_kinnable_id_index` (`kinnable_type`,`kinnable_id`),
  KEY `next_of_kin_tenant_id_index` (`tenant_id`),
  CONSTRAINT `next_of_kin_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `next_of_kin`
--

/*!40000 ALTER TABLE `next_of_kin` DISABLE KEYS */;
/*!40000 ALTER TABLE `next_of_kin` ENABLE KEYS */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;

--
-- Table structure for table `payment_days`
--

DROP TABLE IF EXISTS `payment_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_days` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_days_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_days`
--

/*!40000 ALTER TABLE `payment_days` DISABLE KEYS */;
INSERT INTO `payment_days` VALUES (1,'1',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'2',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'3',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'4',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'5',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(6,'6',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(7,'7',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(8,'8',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(9,'9',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(10,'10',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(11,'11',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(12,'12',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(13,'13',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(14,'14',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(15,'15',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(16,'16',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(17,'17',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(18,'18',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(19,'19',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(20,'20',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(21,'21',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(22,'22',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(23,'23',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(24,'24',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(25,'25',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(26,'26',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(27,'27',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(28,'28',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(29,'29',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(30,'30',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(31,'31',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `payment_days` ENABLE KEYS */;

--
-- Table structure for table `payment_frequencies`
--

DROP TABLE IF EXISTS `payment_frequencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_frequencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_frequencies_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_frequencies`
--

/*!40000 ALTER TABLE `payment_frequencies` DISABLE KEYS */;
INSERT INTO `payment_frequencies` VALUES (1,'Monthly',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Annually',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Once off',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `payment_frequencies` ENABLE KEYS */;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Credit Card',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Cash Payment',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Debit Order',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'EFT',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'Stop Order',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view:acl-settings',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(2,'viewAny:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(3,'view:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(4,'create:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(5,'update:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(6,'delete:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(7,'restore:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(8,'forceDelete:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(9,'import:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(10,'export:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(11,'viewAuditTrail:modules',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(12,'viewAny:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(13,'view:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(14,'create:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(15,'update:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(16,'delete:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(17,'restore:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(18,'forceDelete:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(19,'import:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(20,'export:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(21,'viewAuditTrail:roles',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(22,'viewAny:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(23,'view:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(24,'create:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(25,'update:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(26,'delete:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(27,'restore:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(28,'forceDelete:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(29,'import:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(30,'export:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(31,'viewAuditTrail:permissions',NULL,'web',2,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(32,'viewAny:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(33,'view:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(34,'create:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(35,'update:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(36,'delete:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(37,'restore:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(38,'forceDelete:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(39,'import:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(40,'export:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(41,'crud-settings:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(42,'viewAuditTrail:communications',NULL,'web',3,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(43,'viewAny:dashboards',NULL,'web',4,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(44,'view:dashboards',NULL,'web',4,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(45,'viewAny:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(46,'view:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(47,'create:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(48,'update:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(49,'delete:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(50,'restore:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(51,'forceDelete:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(52,'import:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(53,'export:reports',NULL,'web',9,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(54,'viewAny:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(55,'view:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(56,'create:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(57,'update:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(58,'delete:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(59,'restore:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(60,'forceDelete:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(61,'import:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(62,'export:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(63,'crud-settings:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(64,'viewAuditTrail:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(65,'manageOwnData:tenants',NULL,'web',14,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(66,'viewAny:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(67,'view:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(68,'create:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(69,'update:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(70,'delete:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(71,'restore:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(72,'forceDelete:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(73,'import:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(74,'export:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(75,'crud-settings:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(76,'viewAuditTrail:users',NULL,'web',15,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(77,'root:manage',NULL,'web',10,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(78,'view:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(79,'create:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(80,'update:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(81,'delete:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(82,'restore:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(83,'forceDelete:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(84,'import:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(85,'export:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(86,'viewAuditTrail:settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(87,'view:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(88,'create:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(89,'update:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(90,'delete:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(91,'restore:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(92,'forceDelete:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(93,'import:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(94,'export:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(95,'viewAuditTrail:institution-settings',NULL,'web',11,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(96,'viewAny:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(97,'view:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(98,'create:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(99,'update:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(100,'delete:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(101,'restore:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(102,'forceDelete:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(103,'import:department-metadata',NULL,'web',7,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(104,'export:department-metadata',NULL,'web',7,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(105,'viewAuditTrail:department-metadata',NULL,'web',7,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(106,'viewAny:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(107,'view:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(108,'create:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(109,'update:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(110,'delete:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(111,'restore:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(112,'forceDelete:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(113,'import:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(114,'export:bank-details',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(115,'viewAny:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(116,'view:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(117,'create:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(118,'update:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(119,'delete:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(120,'restore:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(121,'forceDelete:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(122,'import:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(123,'export:addresses',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(124,'viewAny:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(125,'view:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(126,'create:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(127,'update:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(128,'delete:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(129,'restore:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(130,'forceDelete:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(131,'import:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(132,'export:contacts',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(133,'viewAny:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(134,'view:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(135,'create:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(136,'update:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(137,'delete:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(138,'restore:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(139,'forceDelete:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(140,'import:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(141,'export:next-of-kins',NULL,'web',12,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(142,'viewOwnDashboard:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(143,'manageOwnStudentPersonalDetails:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(144,'manageOwnStudentProgramDetails:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(145,'manageOwnStudentSponsorDetails:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(146,'manageOwnStudentContactDetails:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(147,'manageOwnStudentFinancialDetails:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(148,'manageOwnStudentAcademicDetails:students',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(149,'manageStudentMetadata:admin',NULL,'web',13,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;

--
-- Table structure for table `provinces`
--

DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provinces_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provinces`
--

/*!40000 ALTER TABLE `provinces` DISABLE KEYS */;
INSERT INTO `provinces` VALUES (1,'Bulawayo',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Harare',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Manicaland',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'Mashonaland Central',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'Mashonaland East',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(6,'Mashonaland West',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(7,'Masvingo',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(8,'Matebeleland North',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(9,'Matebeleland South',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(10,'Midlands',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(11,'Unknown Province',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `provinces` ENABLE KEYS */;

--
-- Table structure for table `races`
--

DROP TABLE IF EXISTS `races`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `races` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `races_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `races`
--

/*!40000 ALTER TABLE `races` DISABLE KEYS */;
INSERT INTO `races` VALUES (1,'African',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(2,'Black',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(3,'White',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(4,'Colored',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(5,'Indian',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `races` ENABLE KEYS */;

--
-- Table structure for table `relationships`
--

DROP TABLE IF EXISTS `relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relationships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `relationships_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `relationships`
--

/*!40000 ALTER TABLE `relationships` DISABLE KEYS */;
INSERT INTO `relationships` VALUES (1,'Parent',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Spouse',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Guardian',NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `relationships` ENABLE KEYS */;

--
-- Table structure for table `religions`
--

DROP TABLE IF EXISTS `religions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `religions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `religions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `religions`
--

/*!40000 ALTER TABLE `religions` DISABLE KEYS */;
INSERT INTO `religions` VALUES (1,'Christianity',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'African Traditional Religion',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Islam',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'Hinduism',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(5,'Buddhism',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(6,'Judaism',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(7,'Other Religions',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(8,'Religiously Unaffiliated',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `religions` ENABLE KEYS */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(149,1),(142,27),(143,27),(144,27),(145,27),(146,27),(147,27),(148,27);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Administrator','Has full access to all system features and administrative controls.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(2,'Principal','The chief executive of the college, responsible for overall leadership.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(3,'Vice Principal','Assists the principal in managing academic and administrative tasks.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(4,'Registrar','Oversees student records, registration, and institutional data.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(5,'Selection officer','Manages applicant evaluation and admission selections.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(6,'Dean','Leads an academic faculty and manages teaching and research efforts.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(7,'Head of department','Directs the operations of a specific academic department.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(8,'Head of division','Oversees a group of departments within a school or faculty.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(9,'Lecturer','Delivers lectures and academic content to students.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(10,'Lecturer In Charge','Leads a teaching team and manages course delivery.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(11,'Senior Lecturer','Experienced academic with additional teaching and mentoring responsibilities.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(12,'Tutor','Supports student learning in small group or individual settings.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(13,'Researcher','Conducts academic research and publishes scholarly work.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(14,'Admissions officer','Handles student applications and enrollment processing.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(15,'Bursar','Manages college finances, budgeting, and student billing.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(16,'HR officer','Administers staff recruitment, payroll, and compliance.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(17,'Administrative Assistant','Provides clerical and logistical support to departments.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(18,'Student Affairs Officer','Supports student life, welfare, and extracurricular engagement.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(19,'IT Support Technician','Maintains IT infrastructure and provides user support.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(20,'Lab Technician','Prepares lab equipment and assists with practical sessions.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(21,'Librarian','Manages library resources and supports academic research.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(22,'Maintenance staff','Performs repairs and ensures facility upkeep.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(23,'Security officer','Provides safety and security across campus.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(24,'Custodian','Maintains cleanliness and order in college buildings.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(25,'Grounds keeper','Takes care of lawns, gardens, and outdoor spaces.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(26,'Transport Officer','Coordinates campus transport and vehicle logistics.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(27,'Student','A learner enrolled in the institution’s academic programs.','web','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

--
-- Table structure for table `sponsor_types`
--

DROP TABLE IF EXISTS `sponsor_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sponsor_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sponsor_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sponsor_types`
--

/*!40000 ALTER TABLE `sponsor_types` DISABLE KEYS */;
INSERT INTO `sponsor_types` VALUES (1,'Person',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(2,'Company',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(3,'Church',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL),(4,'Other Organization',NULL,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `sponsor_types` ENABLE KEYS */;

--
-- Table structure for table `sponsors`
--

DROP TABLE IF EXISTS `sponsors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sponsors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `sponsor_type_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sponsors_student_id_foreign` (`student_id`),
  KEY `sponsors_tenant_id_index` (`tenant_id`),
  CONSTRAINT `sponsors_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `sponsors_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sponsors`
--

/*!40000 ALTER TABLE `sponsors` DISABLE KEYS */;
/*!40000 ALTER TABLE `sponsors` ENABLE KEYS */;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `employment_type_id` bigint unsigned DEFAULT NULL,
  `employee_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_id` bigint unsigned NOT NULL,
  `gender_id` bigint unsigned NOT NULL,
  `marital_status_id` bigint unsigned NOT NULL,
  `race_id` bigint unsigned DEFAULT NULL,
  `id_type` enum('zimbabwean-national-id-number','foreign-passport-number') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_permit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `religion_id` bigint unsigned DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `denomination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_employee_number_unique` (`employee_number`),
  UNIQUE KEY `staff_staff_id_number_unique` (`staff_id_number`),
  KEY `staff_tenant_id_foreign` (`tenant_id`),
  KEY `staff_user_id_foreign` (`user_id`),
  KEY `staff_employment_type_id_foreign` (`employment_type_id`),
  KEY `staff_title_id_foreign` (`title_id`),
  KEY `staff_gender_id_foreign` (`gender_id`),
  KEY `staff_marital_status_id_foreign` (`marital_status_id`),
  CONSTRAINT `staff_employment_type_id_foreign` FOREIGN KEY (`employment_type_id`) REFERENCES `employment_types` (`id`),
  CONSTRAINT `staff_gender_id_foreign` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`),
  CONSTRAINT `staff_marital_status_id_foreign` FOREIGN KEY (`marital_status_id`) REFERENCES `marital_statuses` (`id`),
  CONSTRAINT `staff_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `staff_title_id_foreign` FOREIGN KEY (`title_id`) REFERENCES `titles` (`id`),
  CONSTRAINT `staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;

--
-- Table structure for table `statuses`
--

DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statuses_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statuses`
--

/*!40000 ALTER TABLE `statuses` DISABLE KEYS */;
INSERT INTO `statuses` VALUES (1,'Active','Currently active and in use','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(2,'Waiting Approval','Pending approval from an authority','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(3,'Inactive','Not currently active','2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `statuses` ENABLE KEYS */;

--
-- Table structure for table `student_programs`
--

DROP TABLE IF EXISTS `student_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `institution_department_id` bigint unsigned NOT NULL,
  `department_level_id` bigint unsigned NOT NULL,
  `department_course_id` bigint unsigned NOT NULL,
  `application_status_id` bigint unsigned DEFAULT NULL,
  `o_level_subjects` json DEFAULT NULL,
  `required_level_completed` tinyint(1) DEFAULT NULL,
  `read_write_acknowledged` tinyint(1) DEFAULT NULL,
  `application_tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_programs_student_id_foreign` (`student_id`),
  KEY `student_programs_institution_department_id_foreign` (`institution_department_id`),
  KEY `student_programs_department_level_id_foreign` (`department_level_id`),
  KEY `student_programs_department_course_id_foreign` (`department_course_id`),
  KEY `student_programs_tenant_id_index` (`tenant_id`),
  CONSTRAINT `student_programs_department_course_id_foreign` FOREIGN KEY (`department_course_id`) REFERENCES `department_courses` (`id`),
  CONSTRAINT `student_programs_department_level_id_foreign` FOREIGN KEY (`department_level_id`) REFERENCES `department_levels` (`id`),
  CONSTRAINT `student_programs_institution_department_id_foreign` FOREIGN KEY (`institution_department_id`) REFERENCES `institution_departments` (`id`),
  CONSTRAINT `student_programs_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `student_programs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_programs`
--

/*!40000 ALTER TABLE `student_programs` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_programs` ENABLE KEYS */;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `title_id` bigint unsigned NOT NULL,
  `gender_id` bigint unsigned NOT NULL,
  `marital_status_id` bigint unsigned NOT NULL,
  `race_id` bigint unsigned DEFAULT NULL,
  `id_type` enum('zimbabwean-national-id-number','foreign-passport-number') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `religion_id` bigint unsigned DEFAULT NULL,
  `study_permit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `denomination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_user_id_unique` (`user_id`),
  KEY `students_tenant_id_foreign` (`tenant_id`),
  KEY `students_title_id_foreign` (`title_id`),
  KEY `students_gender_id_foreign` (`gender_id`),
  KEY `students_marital_status_id_foreign` (`marital_status_id`),
  CONSTRAINT `students_gender_id_foreign` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`),
  CONSTRAINT `students_marital_status_id_foreign` FOREIGN KEY (`marital_status_id`) REFERENCES `marital_statuses` (`id`),
  CONSTRAINT `students_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `students_title_id_foreign` FOREIGN KEY (`title_id`) REFERENCES `titles` (`id`),
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Accounts',1,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(2,'Agriculture',2,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(3,'Art',3,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(4,'Bible Knowledge',4,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(5,'Building Studies',5,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(6,'Business and Enterprise Skills',6,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(7,'Business Studies',7,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(8,'Chinese',8,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(9,'Commerce',9,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(10,'Computer Science',10,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(11,'Design and Technology',11,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(12,'Economics',12,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(13,'English',13,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(14,'Fashion and Fabrics',14,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(15,'Food and Nutrition',15,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(16,'French',16,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(17,'Geography',17,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(18,'German',18,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(19,'History',19,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(20,'Integrated Science',20,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(21,'Literature in English',21,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(22,'Mathematics',22,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(23,'Metal Technology and Design',23,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(24,'Music',24,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(25,'Ndebele',25,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(26,'Physical Education, Sport and Mass Displays',26,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(27,'Religious Studies',27,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(28,'Shona',28,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(29,'Spanish',29,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(30,'Technical Graphics',30,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL),(31,'Wood Technology and Design',31,NULL,'2025-07-04 06:52:01','2025-07-04 06:52:01',NULL);
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'Harare Poly',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL),(2,'Penstej Systems',NULL,'2025-07-04 06:51:58','2025-07-04 06:51:58',NULL);
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;

--
-- Table structure for table `titles`
--

DROP TABLE IF EXISTS `titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `titles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `titles`
--

/*!40000 ALTER TABLE `titles` DISABLE KEYS */;
INSERT INTO `titles` VALUES (1,'Mr',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(2,'Mrs',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(3,'Miss',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(4,'Dr',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL),(5,'Prof',NULL,'2025-07-04 06:51:59','2025-07-04 06:51:59',NULL);
/*!40000 ALTER TABLE `titles` ENABLE KEYS */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `avatar_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_count` int unsigned NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_number_unique` (`phone_number`),
  KEY `users_status_id_foreign` (`status_id`),
  KEY `users_tenant_id_index` (`tenant_id`),
  CONSTRAINT `users_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`),
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,NULL,'Super','','Administrator','penstejdevelopers@gmail.com','+27788104809','2025-07-04 06:51:59','$2y$12$27HLVk0QdV5lJ39Y/a5KUOzcw0kEeL0rJNCow6YomynY0orV/em26',NULL,0,NULL,1,'2025-07-04 06:52:00','2025-07-04 06:52:00',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;

--
-- Dumping routines for database 'hrepoly'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-04 10:52:36
