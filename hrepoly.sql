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
INSERT INTO `academic_levels` VALUES (1,'Primary school',NULL,1,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Secondary school',NULL,2,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Advanced Level',NULL,3,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Tertiary Level',NULL,4,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=820 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'Tenant','created','App\\Models\\Tenants\\Tenant','created',1,NULL,NULL,'{\"attributes\": {\"meta\": null, \"name\": \"Harare Poly\", \"is_active\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(2,'Tenant','created','App\\Models\\Tenants\\Tenant','created',2,NULL,NULL,'{\"attributes\": {\"meta\": null, \"name\": \"Penstej Systems\", \"is_active\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(3,'AclModule','created','App\\Models\\Acl\\Module','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"accommodations\", \"title\": \"Accommodations\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(4,'AclModule','created','App\\Models\\Acl\\Module','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"acl\", \"title\": \"Acl\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(5,'AclModule','created','App\\Models\\Acl\\Module','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"communications\", \"title\": \"Communications\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(6,'AclModule','created','App\\Models\\Acl\\Module','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"dashboards\", \"title\": \"Dashboards\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(7,'AclModule','created','App\\Models\\Acl\\Module','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"enrolments\", \"title\": \"Enrolments\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(8,'AclModule','created','App\\Models\\Acl\\Module','created',6,NULL,NULL,'{\"attributes\": {\"slug\": \"examinations\", \"title\": \"Examinations\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(9,'AclModule','created','App\\Models\\Acl\\Module','created',7,NULL,NULL,'{\"attributes\": {\"slug\": \"institution\", \"title\": \"Institution\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(10,'AclModule','created','App\\Models\\Acl\\Module','created',8,NULL,NULL,'{\"attributes\": {\"slug\": \"other\", \"title\": \"Other\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(11,'AclModule','created','App\\Models\\Acl\\Module','created',9,NULL,NULL,'{\"attributes\": {\"slug\": \"reports\", \"title\": \"Reports\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(12,'AclModule','created','App\\Models\\Acl\\Module','created',10,NULL,NULL,'{\"attributes\": {\"slug\": \"root\", \"title\": \"Root\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(13,'AclModule','created','App\\Models\\Acl\\Module','created',11,NULL,NULL,'{\"attributes\": {\"slug\": \"settings\", \"title\": \"Settings\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(14,'AclModule','created','App\\Models\\Acl\\Module','created',12,NULL,NULL,'{\"attributes\": {\"slug\": \"shared\", \"title\": \"Shared\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(15,'AclModule','created','App\\Models\\Acl\\Module','created',13,NULL,NULL,'{\"attributes\": {\"slug\": \"students\", \"title\": \"Students\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(16,'AclModule','created','App\\Models\\Acl\\Module','created',14,NULL,NULL,'{\"attributes\": {\"slug\": \"tenants\", \"title\": \"Tenants\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(17,'AclModule','created','App\\Models\\Acl\\Module','created',15,NULL,NULL,'{\"attributes\": {\"slug\": \"users\", \"title\": \"Users\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(18,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super User\", \"slug\": \"super-user\", \"description\": \"System-level user with access to all areas.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(19,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"TESC\", \"slug\": \"tesc\", \"description\": \"Tertiary Education Service Council (TESC) group.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(20,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Executive\", \"slug\": \"executive\", \"description\": \"Executive leadership including principals, deans, registrars, and bursars.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(21,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Academic\", \"slug\": \"academic\", \"description\": \"Teaching and research personnel such as lecturers heads of department.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(22,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Administrative\", \"slug\": \"administrative\", \"description\": \"Administrative Staff (Non-Academic) involved in administration.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(23,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Managerial\", \"slug\": \"managerial\", \"description\": \"Managerial Staff (Non-Academic) involved in management.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(24,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Service and support\", \"slug\": \"service-and-support\", \"description\": \"Support and Service Staff (Non-Academic, Operational) providing technical, clerical, or facility-related support.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(25,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Student\", \"slug\": \"student\", \"description\": \"Registered learners in the institution.\"}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(26,'Permission','created','App\\Models\\Acl\\Permission','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"view:acl-settings\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(27,'Permission','created','App\\Models\\Acl\\Permission','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(28,'Permission','created','App\\Models\\Acl\\Permission','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"view:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(29,'Permission','created','App\\Models\\Acl\\Permission','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"create:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(30,'Permission','created','App\\Models\\Acl\\Permission','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"update:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(31,'Permission','created','App\\Models\\Acl\\Permission','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"delete:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(32,'Permission','created','App\\Models\\Acl\\Permission','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"restore:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(33,'Permission','created','App\\Models\\Acl\\Permission','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(34,'Permission','created','App\\Models\\Acl\\Permission','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"import:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(35,'Permission','created','App\\Models\\Acl\\Permission','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"export:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(36,'Permission','created','App\\Models\\Acl\\Permission','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(37,'Permission','created','App\\Models\\Acl\\Permission','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(38,'Permission','created','App\\Models\\Acl\\Permission','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"view:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(39,'Permission','created','App\\Models\\Acl\\Permission','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"create:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(40,'Permission','created','App\\Models\\Acl\\Permission','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"update:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(41,'Permission','created','App\\Models\\Acl\\Permission','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"delete:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(42,'Permission','created','App\\Models\\Acl\\Permission','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"restore:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(43,'Permission','created','App\\Models\\Acl\\Permission','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(44,'Permission','created','App\\Models\\Acl\\Permission','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"import:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(45,'Permission','created','App\\Models\\Acl\\Permission','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"export:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(46,'Permission','created','App\\Models\\Acl\\Permission','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(47,'Permission','created','App\\Models\\Acl\\Permission','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(48,'Permission','created','App\\Models\\Acl\\Permission','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"view:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(49,'Permission','created','App\\Models\\Acl\\Permission','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"create:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(50,'Permission','created','App\\Models\\Acl\\Permission','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"update:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(51,'Permission','created','App\\Models\\Acl\\Permission','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"delete:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(52,'Permission','created','App\\Models\\Acl\\Permission','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"restore:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(53,'Permission','created','App\\Models\\Acl\\Permission','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(54,'Permission','created','App\\Models\\Acl\\Permission','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"import:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(55,'Permission','created','App\\Models\\Acl\\Permission','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"export:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(56,'Permission','created','App\\Models\\Acl\\Permission','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(57,'Permission','created','App\\Models\\Acl\\Permission','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(58,'Permission','created','App\\Models\\Acl\\Permission','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"view:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10'),(59,'Permission','created','App\\Models\\Acl\\Permission','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"create:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(60,'Permission','created','App\\Models\\Acl\\Permission','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"update:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(61,'Permission','created','App\\Models\\Acl\\Permission','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"delete:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(62,'Permission','created','App\\Models\\Acl\\Permission','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"restore:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(63,'Permission','created','App\\Models\\Acl\\Permission','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(64,'Permission','created','App\\Models\\Acl\\Permission','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"import:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(65,'Permission','created','App\\Models\\Acl\\Permission','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"export:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(66,'Permission','created','App\\Models\\Acl\\Permission','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(67,'Permission','created','App\\Models\\Acl\\Permission','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(68,'Permission','created','App\\Models\\Acl\\Permission','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:dashboards\", \"module_id\": 4, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(69,'Permission','created','App\\Models\\Acl\\Permission','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"view:dashboards\", \"module_id\": 4, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(70,'Permission','created','App\\Models\\Acl\\Permission','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(71,'Permission','created','App\\Models\\Acl\\Permission','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"view:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(72,'Permission','created','App\\Models\\Acl\\Permission','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"create:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(73,'Permission','created','App\\Models\\Acl\\Permission','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"update:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(74,'Permission','created','App\\Models\\Acl\\Permission','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"delete:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(75,'Permission','created','App\\Models\\Acl\\Permission','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"restore:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(76,'Permission','created','App\\Models\\Acl\\Permission','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(77,'Permission','created','App\\Models\\Acl\\Permission','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"import:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(78,'Permission','created','App\\Models\\Acl\\Permission','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"export:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(79,'Permission','created','App\\Models\\Acl\\Permission','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(80,'Permission','created','App\\Models\\Acl\\Permission','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"view:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(81,'Permission','created','App\\Models\\Acl\\Permission','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"create:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(82,'Permission','created','App\\Models\\Acl\\Permission','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"update:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(83,'Permission','created','App\\Models\\Acl\\Permission','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"delete:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(84,'Permission','created','App\\Models\\Acl\\Permission','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"restore:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(85,'Permission','created','App\\Models\\Acl\\Permission','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(86,'Permission','created','App\\Models\\Acl\\Permission','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"import:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(87,'Permission','created','App\\Models\\Acl\\Permission','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"export:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(88,'Permission','created','App\\Models\\Acl\\Permission','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(89,'Permission','created','App\\Models\\Acl\\Permission','created',64,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(90,'Permission','created','App\\Models\\Acl\\Permission','created',65,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnData:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(91,'Permission','created','App\\Models\\Acl\\Permission','created',66,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(92,'Permission','created','App\\Models\\Acl\\Permission','created',67,NULL,NULL,'{\"attributes\": {\"name\": \"view:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(93,'Permission','created','App\\Models\\Acl\\Permission','created',68,NULL,NULL,'{\"attributes\": {\"name\": \"create:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(94,'Permission','created','App\\Models\\Acl\\Permission','created',69,NULL,NULL,'{\"attributes\": {\"name\": \"update:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(95,'Permission','created','App\\Models\\Acl\\Permission','created',70,NULL,NULL,'{\"attributes\": {\"name\": \"delete:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(96,'Permission','created','App\\Models\\Acl\\Permission','created',71,NULL,NULL,'{\"attributes\": {\"name\": \"restore:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(97,'Permission','created','App\\Models\\Acl\\Permission','created',72,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(98,'Permission','created','App\\Models\\Acl\\Permission','created',73,NULL,NULL,'{\"attributes\": {\"name\": \"import:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(99,'Permission','created','App\\Models\\Acl\\Permission','created',74,NULL,NULL,'{\"attributes\": {\"name\": \"export:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(100,'Permission','created','App\\Models\\Acl\\Permission','created',75,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(101,'Permission','created','App\\Models\\Acl\\Permission','created',76,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(102,'Permission','created','App\\Models\\Acl\\Permission','created',77,NULL,NULL,'{\"attributes\": {\"name\": \"root:manage\", \"module_id\": 10, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(103,'Permission','created','App\\Models\\Acl\\Permission','created',78,NULL,NULL,'{\"attributes\": {\"name\": \"view:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(104,'Permission','created','App\\Models\\Acl\\Permission','created',79,NULL,NULL,'{\"attributes\": {\"name\": \"create:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(105,'Permission','created','App\\Models\\Acl\\Permission','created',80,NULL,NULL,'{\"attributes\": {\"name\": \"update:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(106,'Permission','created','App\\Models\\Acl\\Permission','created',81,NULL,NULL,'{\"attributes\": {\"name\": \"delete:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(107,'Permission','created','App\\Models\\Acl\\Permission','created',82,NULL,NULL,'{\"attributes\": {\"name\": \"restore:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(108,'Permission','created','App\\Models\\Acl\\Permission','created',83,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(109,'Permission','created','App\\Models\\Acl\\Permission','created',84,NULL,NULL,'{\"attributes\": {\"name\": \"import:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(110,'Permission','created','App\\Models\\Acl\\Permission','created',85,NULL,NULL,'{\"attributes\": {\"name\": \"export:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(111,'Permission','created','App\\Models\\Acl\\Permission','created',86,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(112,'Permission','created','App\\Models\\Acl\\Permission','created',87,NULL,NULL,'{\"attributes\": {\"name\": \"view:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(113,'Permission','created','App\\Models\\Acl\\Permission','created',88,NULL,NULL,'{\"attributes\": {\"name\": \"create:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(114,'Permission','created','App\\Models\\Acl\\Permission','created',89,NULL,NULL,'{\"attributes\": {\"name\": \"update:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(115,'Permission','created','App\\Models\\Acl\\Permission','created',90,NULL,NULL,'{\"attributes\": {\"name\": \"delete:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(116,'Permission','created','App\\Models\\Acl\\Permission','created',91,NULL,NULL,'{\"attributes\": {\"name\": \"restore:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(117,'Permission','created','App\\Models\\Acl\\Permission','created',92,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(118,'Permission','created','App\\Models\\Acl\\Permission','created',93,NULL,NULL,'{\"attributes\": {\"name\": \"import:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(119,'Permission','created','App\\Models\\Acl\\Permission','created',94,NULL,NULL,'{\"attributes\": {\"name\": \"export:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(120,'Permission','created','App\\Models\\Acl\\Permission','created',95,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(121,'Permission','created','App\\Models\\Acl\\Permission','created',96,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(122,'Permission','created','App\\Models\\Acl\\Permission','created',97,NULL,NULL,'{\"attributes\": {\"name\": \"view:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(123,'Permission','created','App\\Models\\Acl\\Permission','created',98,NULL,NULL,'{\"attributes\": {\"name\": \"create:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(124,'Permission','created','App\\Models\\Acl\\Permission','created',99,NULL,NULL,'{\"attributes\": {\"name\": \"update:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(125,'Permission','created','App\\Models\\Acl\\Permission','created',100,NULL,NULL,'{\"attributes\": {\"name\": \"delete:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(126,'Permission','created','App\\Models\\Acl\\Permission','created',101,NULL,NULL,'{\"attributes\": {\"name\": \"restore:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(127,'Permission','created','App\\Models\\Acl\\Permission','created',102,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(128,'Permission','created','App\\Models\\Acl\\Permission','created',103,NULL,NULL,'{\"attributes\": {\"name\": \"import:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(129,'Permission','created','App\\Models\\Acl\\Permission','created',104,NULL,NULL,'{\"attributes\": {\"name\": \"export:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(130,'Permission','created','App\\Models\\Acl\\Permission','created',105,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(131,'Permission','created','App\\Models\\Acl\\Permission','created',106,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(132,'Permission','created','App\\Models\\Acl\\Permission','created',107,NULL,NULL,'{\"attributes\": {\"name\": \"view:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(133,'Permission','created','App\\Models\\Acl\\Permission','created',108,NULL,NULL,'{\"attributes\": {\"name\": \"create:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(134,'Permission','created','App\\Models\\Acl\\Permission','created',109,NULL,NULL,'{\"attributes\": {\"name\": \"update:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(135,'Permission','created','App\\Models\\Acl\\Permission','created',110,NULL,NULL,'{\"attributes\": {\"name\": \"delete:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(136,'Permission','created','App\\Models\\Acl\\Permission','created',111,NULL,NULL,'{\"attributes\": {\"name\": \"restore:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(137,'Permission','created','App\\Models\\Acl\\Permission','created',112,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(138,'Permission','created','App\\Models\\Acl\\Permission','created',113,NULL,NULL,'{\"attributes\": {\"name\": \"import:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(139,'Permission','created','App\\Models\\Acl\\Permission','created',114,NULL,NULL,'{\"attributes\": {\"name\": \"export:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(140,'Permission','created','App\\Models\\Acl\\Permission','created',115,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(141,'Permission','created','App\\Models\\Acl\\Permission','created',116,NULL,NULL,'{\"attributes\": {\"name\": \"view:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(142,'Permission','created','App\\Models\\Acl\\Permission','created',117,NULL,NULL,'{\"attributes\": {\"name\": \"create:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(143,'Permission','created','App\\Models\\Acl\\Permission','created',118,NULL,NULL,'{\"attributes\": {\"name\": \"update:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(144,'Permission','created','App\\Models\\Acl\\Permission','created',119,NULL,NULL,'{\"attributes\": {\"name\": \"delete:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(145,'Permission','created','App\\Models\\Acl\\Permission','created',120,NULL,NULL,'{\"attributes\": {\"name\": \"restore:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(146,'Permission','created','App\\Models\\Acl\\Permission','created',121,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(147,'Permission','created','App\\Models\\Acl\\Permission','created',122,NULL,NULL,'{\"attributes\": {\"name\": \"import:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(148,'Permission','created','App\\Models\\Acl\\Permission','created',123,NULL,NULL,'{\"attributes\": {\"name\": \"export:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(149,'Permission','created','App\\Models\\Acl\\Permission','created',124,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(150,'Permission','created','App\\Models\\Acl\\Permission','created',125,NULL,NULL,'{\"attributes\": {\"name\": \"view:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(151,'Permission','created','App\\Models\\Acl\\Permission','created',126,NULL,NULL,'{\"attributes\": {\"name\": \"create:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(152,'Permission','created','App\\Models\\Acl\\Permission','created',127,NULL,NULL,'{\"attributes\": {\"name\": \"update:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(153,'Permission','created','App\\Models\\Acl\\Permission','created',128,NULL,NULL,'{\"attributes\": {\"name\": \"delete:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(154,'Permission','created','App\\Models\\Acl\\Permission','created',129,NULL,NULL,'{\"attributes\": {\"name\": \"restore:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(155,'Permission','created','App\\Models\\Acl\\Permission','created',130,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(156,'Permission','created','App\\Models\\Acl\\Permission','created',131,NULL,NULL,'{\"attributes\": {\"name\": \"import:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(157,'Permission','created','App\\Models\\Acl\\Permission','created',132,NULL,NULL,'{\"attributes\": {\"name\": \"export:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(158,'Permission','created','App\\Models\\Acl\\Permission','created',133,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(159,'Permission','created','App\\Models\\Acl\\Permission','created',134,NULL,NULL,'{\"attributes\": {\"name\": \"view:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(160,'Permission','created','App\\Models\\Acl\\Permission','created',135,NULL,NULL,'{\"attributes\": {\"name\": \"create:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(161,'Permission','created','App\\Models\\Acl\\Permission','created',136,NULL,NULL,'{\"attributes\": {\"name\": \"update:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(162,'Permission','created','App\\Models\\Acl\\Permission','created',137,NULL,NULL,'{\"attributes\": {\"name\": \"delete:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(163,'Permission','created','App\\Models\\Acl\\Permission','created',138,NULL,NULL,'{\"attributes\": {\"name\": \"restore:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(164,'Permission','created','App\\Models\\Acl\\Permission','created',139,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(165,'Permission','created','App\\Models\\Acl\\Permission','created',140,NULL,NULL,'{\"attributes\": {\"name\": \"import:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(166,'Permission','created','App\\Models\\Acl\\Permission','created',141,NULL,NULL,'{\"attributes\": {\"name\": \"export:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(167,'Permission','created','App\\Models\\Acl\\Permission','created',142,NULL,NULL,'{\"attributes\": {\"name\": \"viewOwnDashboard:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(168,'Permission','created','App\\Models\\Acl\\Permission','created',143,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentPersonalDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(169,'Permission','created','App\\Models\\Acl\\Permission','created',144,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentProgramDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(170,'Permission','created','App\\Models\\Acl\\Permission','created',145,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentSponsorDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(171,'Permission','created','App\\Models\\Acl\\Permission','created',146,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentContactDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(172,'Permission','created','App\\Models\\Acl\\Permission','created',147,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentFinancialDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:11','2025-07-16 06:37:11'),(173,'Permission','created','App\\Models\\Acl\\Permission','created',148,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentAcademicDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(174,'Permission','created','App\\Models\\Acl\\Permission','created',149,NULL,NULL,'{\"attributes\": {\"name\": \"manageStudentMetadata:admin\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(175,'Permission','created','App\\Models\\Acl\\Permission','created',150,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(176,'Permission','created','App\\Models\\Acl\\Permission','created',151,NULL,NULL,'{\"attributes\": {\"name\": \"view:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(177,'Permission','created','App\\Models\\Acl\\Permission','created',152,NULL,NULL,'{\"attributes\": {\"name\": \"create:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(178,'Permission','created','App\\Models\\Acl\\Permission','created',153,NULL,NULL,'{\"attributes\": {\"name\": \"update:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(179,'Permission','created','App\\Models\\Acl\\Permission','created',154,NULL,NULL,'{\"attributes\": {\"name\": \"delete:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(180,'Permission','created','App\\Models\\Acl\\Permission','created',155,NULL,NULL,'{\"attributes\": {\"name\": \"restore:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(181,'Permission','created','App\\Models\\Acl\\Permission','created',156,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(182,'Permission','created','App\\Models\\Acl\\Permission','created',157,NULL,NULL,'{\"attributes\": {\"name\": \"import:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(183,'Permission','created','App\\Models\\Acl\\Permission','created',158,NULL,NULL,'{\"attributes\": {\"name\": \"export:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(184,'Permission','created','App\\Models\\Acl\\Permission','created',159,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(185,'Permission','created','App\\Models\\Acl\\Permission','created',160,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(186,'Permission','created','App\\Models\\Acl\\Permission','created',161,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(187,'Permission','created','App\\Models\\Acl\\Permission','created',162,NULL,NULL,'{\"attributes\": {\"name\": \"view:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(188,'Permission','created','App\\Models\\Acl\\Permission','created',163,NULL,NULL,'{\"attributes\": {\"name\": \"create:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(189,'Permission','created','App\\Models\\Acl\\Permission','created',164,NULL,NULL,'{\"attributes\": {\"name\": \"update:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(190,'Permission','created','App\\Models\\Acl\\Permission','created',165,NULL,NULL,'{\"attributes\": {\"name\": \"delete:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(191,'Permission','created','App\\Models\\Acl\\Permission','created',166,NULL,NULL,'{\"attributes\": {\"name\": \"restore:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(192,'Permission','created','App\\Models\\Acl\\Permission','created',167,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(193,'Permission','created','App\\Models\\Acl\\Permission','created',168,NULL,NULL,'{\"attributes\": {\"name\": \"import:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(194,'Permission','created','App\\Models\\Acl\\Permission','created',169,NULL,NULL,'{\"attributes\": {\"name\": \"export:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(195,'Permission','created','App\\Models\\Acl\\Permission','created',170,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(196,'Permission','created','App\\Models\\Acl\\Permission','created',171,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:enrolments\", \"module_id\": 5, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(197,'Permission','created','App\\Models\\Acl\\Permission','created',172,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(198,'Permission','created','App\\Models\\Acl\\Permission','created',173,NULL,NULL,'{\"attributes\": {\"name\": \"view:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(199,'Permission','created','App\\Models\\Acl\\Permission','created',174,NULL,NULL,'{\"attributes\": {\"name\": \"create:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(200,'Permission','created','App\\Models\\Acl\\Permission','created',175,NULL,NULL,'{\"attributes\": {\"name\": \"update:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(201,'Permission','created','App\\Models\\Acl\\Permission','created',176,NULL,NULL,'{\"attributes\": {\"name\": \"delete:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(202,'Permission','created','App\\Models\\Acl\\Permission','created',177,NULL,NULL,'{\"attributes\": {\"name\": \"restore:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(203,'Permission','created','App\\Models\\Acl\\Permission','created',178,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(204,'Permission','created','App\\Models\\Acl\\Permission','created',179,NULL,NULL,'{\"attributes\": {\"name\": \"import:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(205,'Permission','created','App\\Models\\Acl\\Permission','created',180,NULL,NULL,'{\"attributes\": {\"name\": \"export:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(206,'Permission','created','App\\Models\\Acl\\Permission','created',181,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(207,'Permission','created','App\\Models\\Acl\\Permission','created',182,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:examinations\", \"module_id\": 6, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(208,'Permission','created','App\\Models\\Acl\\Permission','created',183,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(209,'Permission','created','App\\Models\\Acl\\Permission','created',184,NULL,NULL,'{\"attributes\": {\"name\": \"view:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(210,'Permission','created','App\\Models\\Acl\\Permission','created',185,NULL,NULL,'{\"attributes\": {\"name\": \"create:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(211,'Permission','created','App\\Models\\Acl\\Permission','created',186,NULL,NULL,'{\"attributes\": {\"name\": \"update:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(212,'Permission','created','App\\Models\\Acl\\Permission','created',187,NULL,NULL,'{\"attributes\": {\"name\": \"delete:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(213,'Permission','created','App\\Models\\Acl\\Permission','created',188,NULL,NULL,'{\"attributes\": {\"name\": \"restore:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(214,'Permission','created','App\\Models\\Acl\\Permission','created',189,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(215,'Permission','created','App\\Models\\Acl\\Permission','created',190,NULL,NULL,'{\"attributes\": {\"name\": \"import:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(216,'Permission','created','App\\Models\\Acl\\Permission','created',191,NULL,NULL,'{\"attributes\": {\"name\": \"export:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(217,'Permission','created','App\\Models\\Acl\\Permission','created',192,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(218,'Permission','created','App\\Models\\Acl\\Permission','created',193,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:accommodations\", \"module_id\": 1, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(219,'Role','created','App\\Models\\Acl\\Role','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super User\", \"slug\": \"super-user\", \"guard_name\": \"web\", \"description\": \"Power user with elevated privileges for system oversight.\", \"role_group_id\": 1}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(220,'Role','created','App\\Models\\Acl\\Role','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Super Administrator\", \"slug\": \"super-administrator\", \"guard_name\": \"web\", \"description\": \"Has unrestricted access to all system functions.\", \"role_group_id\": 1}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(221,'Role','created','App\\Models\\Acl\\Role','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"TESC\", \"slug\": \"tesc\", \"guard_name\": \"web\", \"description\": \"Tertiary Education Service Council (TESC) group responsible for overseeing tertiary education policies and standards.\", \"role_group_id\": 2}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(222,'Role','created','App\\Models\\Acl\\Role','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Principal\", \"slug\": \"principal\", \"guard_name\": \"web\", \"description\": \"The head of the institution.\", \"role_group_id\": 3}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(223,'Role','created','App\\Models\\Acl\\Role','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Vice Principal\", \"slug\": \"vice-principal\", \"guard_name\": \"web\", \"description\": \"Deputy to the Principal.\", \"role_group_id\": 3}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(224,'Role','created','App\\Models\\Acl\\Role','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Registrar\", \"slug\": \"registrar\", \"guard_name\": \"web\", \"description\": \"Oversees academic records and administrative operations.\", \"role_group_id\": 3}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(225,'Role','created','App\\Models\\Acl\\Role','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Dean\", \"slug\": \"dean\", \"guard_name\": \"web\", \"description\": \"Leads a faculty or academic division.\", \"role_group_id\": 3}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(226,'Role','created','App\\Models\\Acl\\Role','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Bursar\", \"slug\": \"bursar\", \"guard_name\": \"web\", \"description\": \"Oversees and Manages finances of the institution.\", \"role_group_id\": 3}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(227,'Role','created','App\\Models\\Acl\\Role','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Librarian\", \"slug\": \"librarian\", \"guard_name\": \"web\", \"description\": \"Manages library resources and services.\", \"role_group_id\": 3}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(228,'Role','created','App\\Models\\Acl\\Role','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Lecturer\", \"slug\": \"lecturer\", \"guard_name\": \"web\", \"description\": \"Delivers academic content to students.\", \"role_group_id\": 4}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(229,'Role','created','App\\Models\\Acl\\Role','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Lecturer in Charge\", \"slug\": \"lecturer-in-charge\", \"guard_name\": \"web\", \"description\": \"Coordinates lecturers within a module.\", \"role_group_id\": 4}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(230,'Role','created','App\\Models\\Acl\\Role','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Senior Lecturer\", \"slug\": \"senior-lecturer\", \"guard_name\": \"web\", \"description\": \"Senior academic with additional responsibilities.\", \"role_group_id\": 4}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(231,'Role','created','App\\Models\\Acl\\Role','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Head of Division\", \"slug\": \"head-of-division\", \"guard_name\": \"web\", \"description\": \"Leads a division and oversees departments within it.\", \"role_group_id\": 4}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(232,'Role','created','App\\Models\\Acl\\Role','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Head of Department\", \"slug\": \"head-of-department\", \"guard_name\": \"web\", \"description\": \"Responsible for a specific academic department.\", \"role_group_id\": 4}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(233,'Role','created','App\\Models\\Acl\\Role','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Selection Officer\", \"slug\": \"selection-officer\", \"guard_name\": \"web\", \"description\": \"Manages student selection processes.\", \"role_group_id\": 4}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(234,'Role','created','App\\Models\\Acl\\Role','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"IT Manager\", \"slug\": \"it-manager\", \"guard_name\": \"web\", \"description\": \"Oversees IT infrastructure and strategy.\", \"role_group_id\": 6}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(235,'Role','created','App\\Models\\Acl\\Role','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Accountant\", \"slug\": \"accountant\", \"guard_name\": \"web\", \"description\": \"Manages finances of the institution.\", \"role_group_id\": 6}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(236,'Role','created','App\\Models\\Acl\\Role','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"HR Officer\", \"slug\": \"hr-officer\", \"guard_name\": \"web\", \"description\": \"Handles staff recruitment and welfare.\", \"role_group_id\": 6}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(237,'Role','created','App\\Models\\Acl\\Role','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Administrative Officer\", \"slug\": \"administrative-officer\", \"guard_name\": \"web\", \"description\": \"Handles applications and enrollment.\", \"role_group_id\": 6}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(238,'Role','created','App\\Models\\Acl\\Role','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Accountant Assistant\", \"slug\": \"accountant-assistant\", \"guard_name\": \"web\", \"description\": \"Provides support to Accountant.\", \"role_group_id\": 5}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(239,'Role','created','App\\Models\\Acl\\Role','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"HR Officer Assistant\", \"slug\": \"hr-officer-assistant\", \"guard_name\": \"web\", \"description\": \"Helps the HR Officer.\", \"role_group_id\": 5}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(240,'Role','created','App\\Models\\Acl\\Role','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Administrative Assistant\", \"slug\": \"administrative-assistant\", \"guard_name\": \"web\", \"description\": \"Provides administrative support.\", \"role_group_id\": 5}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(241,'Role','created','App\\Models\\Acl\\Role','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"IT Systems Administrator\", \"slug\": \"it-systems-administrator\", \"guard_name\": \"web\", \"description\": \"Provides IT systems administration.\", \"role_group_id\": 5}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(242,'Role','created','App\\Models\\Acl\\Role','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"IT Support Technician\", \"slug\": \"it-support-technician\", \"guard_name\": \"web\", \"description\": \"Provides technical support.\", \"role_group_id\": 7}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(243,'Role','created','App\\Models\\Acl\\Role','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Lab Technician\", \"slug\": \"lab-technician\", \"guard_name\": \"web\", \"description\": \"Prepares and maintains lab equipment.\", \"role_group_id\": 7}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(244,'Role','created','App\\Models\\Acl\\Role','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Security Officer\", \"slug\": \"security-officer\", \"guard_name\": \"web\", \"description\": \"Maintains safety and security.\", \"role_group_id\": 7}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(245,'Role','created','App\\Models\\Acl\\Role','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Student\", \"slug\": \"student\", \"guard_name\": \"web\", \"description\": \"Learner enrolled in the institution.\", \"role_group_id\": 8}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(246,'Gender','created','App\\Models\\Shared\\Gender','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Male\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(247,'Gender','created','App\\Models\\Shared\\Gender','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Female\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(248,'IdType','created','App\\Models\\Shared\\IdType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Zimbabwean National ID\", \"description\": \"A valid Zimbabwean National Identification Number issued by the Registrar General’s Office.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(249,'IdType','created','App\\Models\\Shared\\IdType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Foreign Passport Number\", \"description\": \"A valid passport number issued by a foreign government, subject to verification and approval.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(250,'Title','created','App\\Models\\Shared\\Title','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Mr\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(251,'Title','created','App\\Models\\Shared\\Title','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Mrs\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(252,'Title','created','App\\Models\\Shared\\Title','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Miss\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(253,'Title','created','App\\Models\\Shared\\Title','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Dr\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(254,'Title','created','App\\Models\\Shared\\Title','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Prof\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(255,'Race','created','App\\Models\\Shared\\Race','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"African\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(256,'Race','created','App\\Models\\Shared\\Race','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Black\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(257,'Race','created','App\\Models\\Shared\\Race','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"White\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(258,'Race','created','App\\Models\\Shared\\Race','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Colored\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(259,'Race','created','App\\Models\\Shared\\Race','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Indian\", \"description\": null}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(260,'Status','created','App\\Models\\Shared\\Status','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Active\", \"description\": \"Currently active and in use\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(261,'Status','created','App\\Models\\Shared\\Status','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Waiting Approval\", \"description\": \"Pending approval from an authority\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(262,'Status','created','App\\Models\\Shared\\Status','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Inactive\", \"description\": \"Not currently active\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(263,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Submitted\", \"position\": 1, \"description\": \"Application has been submitted and is awaiting review.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(264,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting Application Fee Payment\", \"position\": 2, \"description\": \"Pending payment of application or registration fees.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(265,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"In Review\", \"position\": 3, \"description\": \"Application is currently under review by staff.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(266,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting Requirements\", \"position\": 4, \"description\": \"Additional documents or info required.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(267,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Interview Scheduled\", \"position\": 5, \"description\": \"Interview has been scheduled with the applicant.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(268,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Interview Completed\", \"position\": 6, \"description\": \"Interview has been completed and is under consideration.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(269,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Decision Pending\", \"position\": 7, \"description\": \"A final admission decision is being made.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(270,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Accepted / Offer Made\", \"position\": 8, \"description\": \"Offer has been made to the applicant.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(271,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Waitlisted\", \"position\": 9, \"description\": \"Applicant has been waitlisted.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(272,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Rejected\", \"position\": 10, \"description\": \"Application has been rejected.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(273,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Offer Accepted\", \"position\": 11, \"description\": \"Offer has been accepted by the applicant.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(274,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Offer Declined\", \"position\": 12, \"description\": \"Applicant declined the offer.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(275,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting fees payment\", \"position\": 13, \"description\": \"Awaiting fees payment.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(276,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Enrolled / Registered\", \"position\": 14, \"description\": \"Applicant has enrolled and completed registration.\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(277,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"send-email-to-applicant\", \"title\": \"Send Email To Applicant\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(278,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"send-email-to-staff\", \"title\": \"Send Email To Staff\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(279,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"create-payment-link\", \"title\": \"Create Payment Link\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(280,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"request-documents\", \"title\": \"Request Documents\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(281,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"verify-identity\", \"title\": \"Verify Identity\"}}',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12'),(282,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',6,NULL,NULL,'{\"attributes\": {\"slug\": \"mark-step-complete\", \"title\": \"Mark Step Complete\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(283,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',7,NULL,NULL,'{\"attributes\": {\"slug\": \"revert-step\", \"title\": \"Revert Step\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(284,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',8,NULL,NULL,'{\"attributes\": {\"slug\": \"upload-receipt\", \"title\": \"Upload Receipt\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(285,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',9,NULL,NULL,'{\"attributes\": {\"slug\": \"add-internal-note\", \"title\": \"Add Internal Note\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(286,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',10,NULL,NULL,'{\"attributes\": {\"slug\": \"assign-staff\", \"title\": \"Assign Staff\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(287,'User','created','App\\Models\\Users\\User','created',1,NULL,NULL,'{\"attributes\": {\"email\": \"penstejdevelopers@gmail.com\", \"password\": \"$2y$12$rsDGlzAJ20PmgqJtu.leXuyBlagYpP.4/.8dKwYBmp2Nny3rpdR1m\", \"avatar_id\": null, \"last_name\": \"Administrator\", \"status_id\": 1, \"tenant_id\": 1, \"first_name\": \"Super\", \"login_count\": 0, \"middle_name\": \"\", \"phone_number\": \"0788104809\", \"last_login_at\": null, \"email_verified_at\": \"2025-07-16T06:37:13.000000Z\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(288,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Email\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(289,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Sms\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(290,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Phone\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(291,'Country','created','App\\Models\\Shared\\Country','created',1,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Afghanistan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(292,'Country','created','App\\Models\\Shared\\Country','created',2,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Albania\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(293,'Country','created','App\\Models\\Shared\\Country','created',3,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Algeria\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(294,'Country','created','App\\Models\\Shared\\Country','created',4,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Andorra\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(295,'Country','created','App\\Models\\Shared\\Country','created',5,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Angola\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(296,'Country','created','App\\Models\\Shared\\Country','created',6,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Antigua and Barbuda\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(297,'Country','created','App\\Models\\Shared\\Country','created',7,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Argentina\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(298,'Country','created','App\\Models\\Shared\\Country','created',8,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Armenia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(299,'Country','created','App\\Models\\Shared\\Country','created',9,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Australia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(300,'Country','created','App\\Models\\Shared\\Country','created',10,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Azerbaijan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(301,'Country','created','App\\Models\\Shared\\Country','created',11,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bahamas\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(302,'Country','created','App\\Models\\Shared\\Country','created',12,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bahrain\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(303,'Country','created','App\\Models\\Shared\\Country','created',13,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bangladesh\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(304,'Country','created','App\\Models\\Shared\\Country','created',14,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Barbados\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(305,'Country','created','App\\Models\\Shared\\Country','created',15,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belgium\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(306,'Country','created','App\\Models\\Shared\\Country','created',16,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belize\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(307,'Country','created','App\\Models\\Shared\\Country','created',17,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Benin\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(308,'Country','created','App\\Models\\Shared\\Country','created',18,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bhutan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(309,'Country','created','App\\Models\\Shared\\Country','created',19,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bolivia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(310,'Country','created','App\\Models\\Shared\\Country','created',20,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bosnia and Herzegovina\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(311,'Country','created','App\\Models\\Shared\\Country','created',21,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Botswana\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(312,'Country','created','App\\Models\\Shared\\Country','created',22,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Brazil\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(313,'Country','created','App\\Models\\Shared\\Country','created',23,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Brunei\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(314,'Country','created','App\\Models\\Shared\\Country','created',24,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bulgaria\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(315,'Country','created','App\\Models\\Shared\\Country','created',25,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Burkina Faso\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(316,'Country','created','App\\Models\\Shared\\Country','created',26,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Burundi\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(317,'Country','created','App\\Models\\Shared\\Country','created',27,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Chile\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(318,'Country','created','App\\Models\\Shared\\Country','created',28,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Colombia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(319,'Country','created','App\\Models\\Shared\\Country','created',29,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Comoros\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(320,'Country','created','App\\Models\\Shared\\Country','created',30,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Costa Rica\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(321,'Country','created','App\\Models\\Shared\\Country','created',31,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Democratic Republic of the Congo\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(322,'Country','created','App\\Models\\Shared\\Country','created',32,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Denmark\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(323,'Country','created','App\\Models\\Shared\\Country','created',33,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Djibouti\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(324,'Country','created','App\\Models\\Shared\\Country','created',34,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Dominica\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(325,'Country','created','App\\Models\\Shared\\Country','created',35,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Dominican Republic\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(326,'Country','created','App\\Models\\Shared\\Country','created',36,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Germany\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(327,'Country','created','App\\Models\\Shared\\Country','created',37,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ecuador\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(328,'Country','created','App\\Models\\Shared\\Country','created',38,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Egypt\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(329,'Country','created','App\\Models\\Shared\\Country','created',39,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Equatorial Guinea\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(330,'Country','created','App\\Models\\Shared\\Country','created',40,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"El Salvador\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(331,'Country','created','App\\Models\\Shared\\Country','created',41,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Eritrea\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(332,'Country','created','App\\Models\\Shared\\Country','created',42,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Estonia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(333,'Country','created','App\\Models\\Shared\\Country','created',43,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ethiopia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(334,'Country','created','App\\Models\\Shared\\Country','created',44,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Fiji\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(335,'Country','created','App\\Models\\Shared\\Country','created',45,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Philippines\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(336,'Country','created','App\\Models\\Shared\\Country','created',46,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Finland\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(337,'Country','created','App\\Models\\Shared\\Country','created',47,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"France\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(338,'Country','created','App\\Models\\Shared\\Country','created',48,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Gabon\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(339,'Country','created','App\\Models\\Shared\\Country','created',49,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Gambia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(340,'Country','created','App\\Models\\Shared\\Country','created',50,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Georgia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(341,'Country','created','App\\Models\\Shared\\Country','created',51,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ghana\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(342,'Country','created','App\\Models\\Shared\\Country','created',52,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Grenada\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(343,'Country','created','App\\Models\\Shared\\Country','created',53,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Greece\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(344,'Country','created','App\\Models\\Shared\\Country','created',54,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guatemala\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(345,'Country','created','App\\Models\\Shared\\Country','created',55,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guinea\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(346,'Country','created','App\\Models\\Shared\\Country','created',56,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guinea-Bissau\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(347,'Country','created','App\\Models\\Shared\\Country','created',57,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guyana\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(348,'Country','created','App\\Models\\Shared\\Country','created',58,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Haiti\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(349,'Country','created','App\\Models\\Shared\\Country','created',59,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Honduras\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(350,'Country','created','App\\Models\\Shared\\Country','created',60,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Hungary\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(351,'Country','created','App\\Models\\Shared\\Country','created',61,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ireland\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(352,'Country','created','App\\Models\\Shared\\Country','created',62,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"India\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(353,'Country','created','App\\Models\\Shared\\Country','created',63,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Indonesia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(354,'Country','created','App\\Models\\Shared\\Country','created',64,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iran\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(355,'Country','created','App\\Models\\Shared\\Country','created',65,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iraq\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(356,'Country','created','App\\Models\\Shared\\Country','created',66,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Israel\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(357,'Country','created','App\\Models\\Shared\\Country','created',67,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Italy\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(358,'Country','created','App\\Models\\Shared\\Country','created',68,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Côte d’Ivoire\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(359,'Country','created','App\\Models\\Shared\\Country','created',69,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Jamaica\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(360,'Country','created','App\\Models\\Shared\\Country','created',70,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Japan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(361,'Country','created','App\\Models\\Shared\\Country','created',71,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Yemen\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(362,'Country','created','App\\Models\\Shared\\Country','created',72,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Jordan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(363,'Country','created','App\\Models\\Shared\\Country','created',73,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cape Verde Islands\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(364,'Country','created','App\\Models\\Shared\\Country','created',74,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cambodia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(365,'Country','created','App\\Models\\Shared\\Country','created',75,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cameroon\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(366,'Country','created','App\\Models\\Shared\\Country','created',76,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Canada\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(367,'Country','created','App\\Models\\Shared\\Country','created',77,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kazakhstan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(368,'Country','created','App\\Models\\Shared\\Country','created',78,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Qatar\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(369,'Country','created','App\\Models\\Shared\\Country','created',79,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kenya\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(370,'Country','created','App\\Models\\Shared\\Country','created',80,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kyrgyzstan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(371,'Country','created','App\\Models\\Shared\\Country','created',81,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kiribati\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(372,'Country','created','App\\Models\\Shared\\Country','created',82,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kuwait\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(373,'Country','created','App\\Models\\Shared\\Country','created',83,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Croatia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(374,'Country','created','App\\Models\\Shared\\Country','created',84,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cuba\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(375,'Country','created','App\\Models\\Shared\\Country','created',85,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Laos\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(376,'Country','created','App\\Models\\Shared\\Country','created',86,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Latvia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(377,'Country','created','App\\Models\\Shared\\Country','created',87,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lesotho\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(378,'Country','created','App\\Models\\Shared\\Country','created',88,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lebanon\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(379,'Country','created','App\\Models\\Shared\\Country','created',89,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Liberia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(380,'Country','created','App\\Models\\Shared\\Country','created',90,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Libya\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(381,'Country','created','App\\Models\\Shared\\Country','created',91,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Liechtenstein\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(382,'Country','created','App\\Models\\Shared\\Country','created',92,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lithuania\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(383,'Country','created','App\\Models\\Shared\\Country','created',93,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Luxembourg\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(384,'Country','created','App\\Models\\Shared\\Country','created',94,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Madagascar\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(385,'Country','created','App\\Models\\Shared\\Country','created',95,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malawi\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(386,'Country','created','App\\Models\\Shared\\Country','created',96,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malaysia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(387,'Country','created','App\\Models\\Shared\\Country','created',97,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Maldives\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(388,'Country','created','App\\Models\\Shared\\Country','created',98,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mali\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(389,'Country','created','App\\Models\\Shared\\Country','created',99,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malta\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(390,'Country','created','App\\Models\\Shared\\Country','created',100,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Morocco\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(391,'Country','created','App\\Models\\Shared\\Country','created',101,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Marshall Islands\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(392,'Country','created','App\\Models\\Shared\\Country','created',102,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mauritania\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(393,'Country','created','App\\Models\\Shared\\Country','created',103,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mauritius\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(394,'Country','created','App\\Models\\Shared\\Country','created',104,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mexico\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(395,'Country','created','App\\Models\\Shared\\Country','created',105,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Myanmar (Burma)\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(396,'Country','created','App\\Models\\Shared\\Country','created',106,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Micronesia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(397,'Country','created','App\\Models\\Shared\\Country','created',107,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Moldova\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(398,'Country','created','App\\Models\\Shared\\Country','created',108,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Monaco\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(399,'Country','created','App\\Models\\Shared\\Country','created',109,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mongolia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(400,'Country','created','App\\Models\\Shared\\Country','created',110,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Montenegro\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(401,'Country','created','App\\Models\\Shared\\Country','created',111,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mozambique\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(402,'Country','created','App\\Models\\Shared\\Country','created',112,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Namibia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(403,'Country','created','App\\Models\\Shared\\Country','created',113,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nauru\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(404,'Country','created','App\\Models\\Shared\\Country','created',114,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nepal\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(405,'Country','created','App\\Models\\Shared\\Country','created',115,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Netherlands\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(406,'Country','created','App\\Models\\Shared\\Country','created',116,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"New Zealand\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(407,'Country','created','App\\Models\\Shared\\Country','created',117,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nicaragua\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(408,'Country','created','App\\Models\\Shared\\Country','created',118,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Niger\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(409,'Country','created','App\\Models\\Shared\\Country','created',119,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nigeria\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(410,'Country','created','App\\Models\\Shared\\Country','created',120,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"North Korea\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(411,'Country','created','App\\Models\\Shared\\Country','created',121,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Norway\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(412,'Country','created','App\\Models\\Shared\\Country','created',122,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ukraine\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(413,'Country','created','App\\Models\\Shared\\Country','created',123,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uzbekistan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(414,'Country','created','App\\Models\\Shared\\Country','created',124,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Oman\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(415,'Country','created','App\\Models\\Shared\\Country','created',125,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Austria\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(416,'Country','created','App\\Models\\Shared\\Country','created',126,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"East Timor\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(417,'Country','created','App\\Models\\Shared\\Country','created',127,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Pakistan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(418,'Country','created','App\\Models\\Shared\\Country','created',128,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Palau\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(419,'Country','created','App\\Models\\Shared\\Country','created',129,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Panama\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(420,'Country','created','App\\Models\\Shared\\Country','created',130,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Papua New Guinea\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(421,'Country','created','App\\Models\\Shared\\Country','created',131,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Paraguay\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(422,'Country','created','App\\Models\\Shared\\Country','created',132,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Peru\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(423,'Country','created','App\\Models\\Shared\\Country','created',133,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Pole/Poland\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(424,'Country','created','App\\Models\\Shared\\Country','created',134,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Portugal\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(425,'Country','created','App\\Models\\Shared\\Country','created',135,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Republic of the Congo\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(426,'Country','created','App\\Models\\Shared\\Country','created',136,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Macedonia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(427,'Country','created','App\\Models\\Shared\\Country','created',137,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Romania\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(428,'Country','created','App\\Models\\Shared\\Country','created',138,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Russia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(429,'Country','created','App\\Models\\Shared\\Country','created',139,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Rwanda\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(430,'Country','created','App\\Models\\Shared\\Country','created',140,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Samoa\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(431,'Country','created','App\\Models\\Shared\\Country','created',141,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"San Marino\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(432,'Country','created','App\\Models\\Shared\\Country','created',142,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Saudi Arabia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(433,'Country','created','App\\Models\\Shared\\Country','created',143,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"São Tomé and Principe\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(434,'Country','created','App\\Models\\Shared\\Country','created',144,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Senegal\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(435,'Country','created','App\\Models\\Shared\\Country','created',145,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Central African Republic\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(436,'Country','created','App\\Models\\Shared\\Country','created',146,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Serbia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(437,'Country','created','App\\Models\\Shared\\Country','created',147,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Seychelles\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(438,'Country','created','App\\Models\\Shared\\Country','created',148,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"China\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(439,'Country','created','App\\Models\\Shared\\Country','created',149,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sierra Leone\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(440,'Country','created','App\\Models\\Shared\\Country','created',150,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Singapore\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(441,'Country','created','App\\Models\\Shared\\Country','created',151,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cyprus\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(442,'Country','created','App\\Models\\Shared\\Country','created',152,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Syria\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(443,'Country','created','App\\Models\\Shared\\Country','created',153,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Slovakia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(444,'Country','created','App\\Models\\Shared\\Country','created',154,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Slovenia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(445,'Country','created','App\\Models\\Shared\\Country','created',155,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sudan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(446,'Country','created','App\\Models\\Shared\\Country','created',156,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Solomon Islands\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(447,'Country','created','App\\Models\\Shared\\Country','created',157,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Somalia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(448,'Country','created','App\\Models\\Shared\\Country','created',158,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Spain\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(449,'Country','created','App\\Models\\Shared\\Country','created',159,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sri Lanka\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(450,'Country','created','App\\Models\\Shared\\Country','created',160,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Saint Kitts and Nevis\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(451,'Country','created','App\\Models\\Shared\\Country','created',161,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"St. Lucia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(452,'Country','created','App\\Models\\Shared\\Country','created',162,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"St. Vincent and the Grenadines\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(453,'Country','created','App\\Models\\Shared\\Country','created',163,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"South Africa\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(454,'Country','created','App\\Models\\Shared\\Country','created',164,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Suid-Korea/South Korea\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(455,'Country','created','App\\Models\\Shared\\Country','created',165,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"South Sudan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(456,'Country','created','App\\Models\\Shared\\Country','created',166,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Suriname\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(457,'Country','created','App\\Models\\Shared\\Country','created',167,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Swaziland\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(458,'Country','created','App\\Models\\Shared\\Country','created',168,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sweden\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(459,'Country','created','App\\Models\\Shared\\Country','created',169,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Switzerland\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(460,'Country','created','App\\Models\\Shared\\Country','created',170,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tajikistan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(461,'Country','created','App\\Models\\Shared\\Country','created',171,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Taiwan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(462,'Country','created','App\\Models\\Shared\\Country','created',172,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tanzania\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(463,'Country','created','App\\Models\\Shared\\Country','created',173,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Thailand\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(464,'Country','created','App\\Models\\Shared\\Country','created',174,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tonga\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(465,'Country','created','App\\Models\\Shared\\Country','created',175,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Togo\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(466,'Country','created','App\\Models\\Shared\\Country','created',176,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Trinidad and Tobago\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(467,'Country','created','App\\Models\\Shared\\Country','created',177,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Chad\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(468,'Country','created','App\\Models\\Shared\\Country','created',178,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Czech Republic\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(469,'Country','created','App\\Models\\Shared\\Country','created',179,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tunisia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(470,'Country','created','App\\Models\\Shared\\Country','created',180,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Turkmenistan\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(471,'Country','created','App\\Models\\Shared\\Country','created',181,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Turkey\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(472,'Country','created','App\\Models\\Shared\\Country','created',182,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tuvalu\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(473,'Country','created','App\\Models\\Shared\\Country','created',183,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uganda\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(474,'Country','created','App\\Models\\Shared\\Country','created',184,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uruguay\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(475,'Country','created','App\\Models\\Shared\\Country','created',185,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Vanuatu\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(476,'Country','created','App\\Models\\Shared\\Country','created',186,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Venezuela\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(477,'Country','created','App\\Models\\Shared\\Country','created',187,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United Arab Emirates\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(478,'Country','created','App\\Models\\Shared\\Country','created',188,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United Kingdom\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(479,'Country','created','App\\Models\\Shared\\Country','created',189,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United States of America\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(480,'Country','created','App\\Models\\Shared\\Country','created',190,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Vietnam\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(481,'Country','created','App\\Models\\Shared\\Country','created',191,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belarus\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(482,'Country','created','App\\Models\\Shared\\Country','created',192,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iceland\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(483,'Country','created','App\\Models\\Shared\\Country','created',193,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Zambia\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(484,'Country','created','App\\Models\\Shared\\Country','created',194,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Zimbabwe\"}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(485,'Religion','created','App\\Models\\Shared\\Religion','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Christianity\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(486,'Religion','created','App\\Models\\Shared\\Religion','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"African Traditional Religion\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(487,'Religion','created','App\\Models\\Shared\\Religion','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Islam\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(488,'Religion','created','App\\Models\\Shared\\Religion','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Hinduism\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(489,'Religion','created','App\\Models\\Shared\\Religion','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Buddhism\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(490,'Religion','created','App\\Models\\Shared\\Religion','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Judaism\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(491,'Religion','created','App\\Models\\Shared\\Religion','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Other Religions\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(492,'Religion','created','App\\Models\\Shared\\Religion','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Religiously Unaffiliated\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(493,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Monthly\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(494,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Annually\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(495,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Once off\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(496,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Credit Card\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(497,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Cash Payment\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(498,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Debit Order\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(499,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"EFT\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(500,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Stop Order\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(501,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"1\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(502,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"2\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(503,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"3\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(504,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"4\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(505,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"5\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(506,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"6\", \"description\": null}}',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13'),(507,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"7\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(508,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',8,NULL,NULL,'{\"attributes\": {\"title\": \"8\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(509,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',9,NULL,NULL,'{\"attributes\": {\"title\": \"9\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(510,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',10,NULL,NULL,'{\"attributes\": {\"title\": \"10\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(511,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',11,NULL,NULL,'{\"attributes\": {\"title\": \"11\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(512,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',12,NULL,NULL,'{\"attributes\": {\"title\": \"12\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(513,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',13,NULL,NULL,'{\"attributes\": {\"title\": \"13\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(514,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',14,NULL,NULL,'{\"attributes\": {\"title\": \"14\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(515,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',15,NULL,NULL,'{\"attributes\": {\"title\": \"15\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(516,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',16,NULL,NULL,'{\"attributes\": {\"title\": \"16\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(517,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',17,NULL,NULL,'{\"attributes\": {\"title\": \"17\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(518,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',18,NULL,NULL,'{\"attributes\": {\"title\": \"18\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(519,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',19,NULL,NULL,'{\"attributes\": {\"title\": \"19\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(520,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',20,NULL,NULL,'{\"attributes\": {\"title\": \"20\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(521,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',21,NULL,NULL,'{\"attributes\": {\"title\": \"21\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(522,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',22,NULL,NULL,'{\"attributes\": {\"title\": \"22\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(523,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',23,NULL,NULL,'{\"attributes\": {\"title\": \"23\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(524,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',24,NULL,NULL,'{\"attributes\": {\"title\": \"24\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(525,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',25,NULL,NULL,'{\"attributes\": {\"title\": \"25\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(526,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',26,NULL,NULL,'{\"attributes\": {\"title\": \"26\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(527,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',27,NULL,NULL,'{\"attributes\": {\"title\": \"27\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(528,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',28,NULL,NULL,'{\"attributes\": {\"title\": \"28\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(529,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',29,NULL,NULL,'{\"attributes\": {\"title\": \"29\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(530,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',30,NULL,NULL,'{\"attributes\": {\"title\": \"30\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(531,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',31,NULL,NULL,'{\"attributes\": {\"title\": \"31\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(532,'Language','created','App\\Models\\Shared\\Language','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"English\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(533,'Province','created','App\\Models\\Shared\\Province','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Bulawayo\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(534,'Province','created','App\\Models\\Shared\\Province','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Harare\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(535,'Province','created','App\\Models\\Shared\\Province','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Manicaland\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(536,'Province','created','App\\Models\\Shared\\Province','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland Central\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(537,'Province','created','App\\Models\\Shared\\Province','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland East\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(538,'Province','created','App\\Models\\Shared\\Province','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland West\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(539,'Province','created','App\\Models\\Shared\\Province','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"Masvingo\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(540,'Province','created','App\\Models\\Shared\\Province','created',8,NULL,NULL,'{\"attributes\": {\"title\": \"Matebeleland North\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(541,'Province','created','App\\Models\\Shared\\Province','created',9,NULL,NULL,'{\"attributes\": {\"title\": \"Matebeleland South\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(542,'Province','created','App\\Models\\Shared\\Province','created',10,NULL,NULL,'{\"attributes\": {\"title\": \"Midlands\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(543,'Province','created','App\\Models\\Shared\\Province','created',11,NULL,NULL,'{\"attributes\": {\"title\": \"Unknown Province\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(544,'District','created','App\\Models\\Shared\\District','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Bulawayo\", \"description\": null, \"province_id\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(545,'District','created','App\\Models\\Shared\\District','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Chitungwiza\", \"description\": null, \"province_id\": 2}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(546,'District','created','App\\Models\\Shared\\District','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Harare\", \"description\": null, \"province_id\": 2}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(547,'District','created','App\\Models\\Shared\\District','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Buhera\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(548,'District','created','App\\Models\\Shared\\District','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Chimanimani\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(549,'District','created','App\\Models\\Shared\\District','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Chipinge\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(550,'District','created','App\\Models\\Shared\\District','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Makoni\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(551,'District','created','App\\Models\\Shared\\District','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Mutare\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(552,'District','created','App\\Models\\Shared\\District','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Mutasa\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(553,'District','created','App\\Models\\Shared\\District','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Nyanga\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(554,'District','created','App\\Models\\Shared\\District','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Bindura\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(555,'District','created','App\\Models\\Shared\\District','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Guruve\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(556,'District','created','App\\Models\\Shared\\District','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Mazowe\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(557,'District','created','App\\Models\\Shared\\District','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Mbire\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(558,'District','created','App\\Models\\Shared\\District','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Mount Darwin\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(559,'District','created','App\\Models\\Shared\\District','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Muzarabani\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(560,'District','created','App\\Models\\Shared\\District','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Rushinga\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(561,'District','created','App\\Models\\Shared\\District','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Shamva\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(562,'District','created','App\\Models\\Shared\\District','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Chikomba\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(563,'District','created','App\\Models\\Shared\\District','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Goromonzi\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(564,'District','created','App\\Models\\Shared\\District','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Marondera\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(565,'District','created','App\\Models\\Shared\\District','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Mudzi\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(566,'District','created','App\\Models\\Shared\\District','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Murehwa\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(567,'District','created','App\\Models\\Shared\\District','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Mutoko\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(568,'District','created','App\\Models\\Shared\\District','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Seke\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(569,'District','created','App\\Models\\Shared\\District','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"UMP (Uzumba-Maramba-Pfungwe)\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(570,'District','created','App\\Models\\Shared\\District','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Wedza (Hwedza)\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(571,'District','created','App\\Models\\Shared\\District','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Chegutu\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(572,'District','created','App\\Models\\Shared\\District','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Hurungwe\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(573,'District','created','App\\Models\\Shared\\District','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Kariba\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(574,'District','created','App\\Models\\Shared\\District','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Makonde\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(575,'District','created','App\\Models\\Shared\\District','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Mhondoro-Ngezi\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(576,'District','created','App\\Models\\Shared\\District','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"Sanyati\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(577,'District','created','App\\Models\\Shared\\District','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"Zvimba\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(578,'District','created','App\\Models\\Shared\\District','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"Bikita\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(579,'District','created','App\\Models\\Shared\\District','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"Chiredzi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(580,'District','created','App\\Models\\Shared\\District','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"Chivi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(581,'District','created','App\\Models\\Shared\\District','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"Gutu\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(582,'District','created','App\\Models\\Shared\\District','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"Masvingo\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(583,'District','created','App\\Models\\Shared\\District','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"Mwenezi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(584,'District','created','App\\Models\\Shared\\District','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"Zaka\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(585,'District','created','App\\Models\\Shared\\District','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"Binga\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(586,'District','created','App\\Models\\Shared\\District','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"Bubi\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(587,'District','created','App\\Models\\Shared\\District','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"Hwange\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(588,'District','created','App\\Models\\Shared\\District','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"Lupane\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(589,'District','created','App\\Models\\Shared\\District','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"Nkayi\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(590,'District','created','App\\Models\\Shared\\District','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"Tsholotsho\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(591,'District','created','App\\Models\\Shared\\District','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"Umguza\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(592,'District','created','App\\Models\\Shared\\District','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"Beitbridge\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(593,'District','created','App\\Models\\Shared\\District','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"Bulilima\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(594,'District','created','App\\Models\\Shared\\District','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"Gwanda\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(595,'District','created','App\\Models\\Shared\\District','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"Insiza\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(596,'District','created','App\\Models\\Shared\\District','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"Mangwe\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(597,'District','created','App\\Models\\Shared\\District','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"Matobo\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(598,'District','created','App\\Models\\Shared\\District','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"Umzingwane\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(599,'District','created','App\\Models\\Shared\\District','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"Chirumhanzu\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(600,'District','created','App\\Models\\Shared\\District','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"Gokwe North\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(601,'District','created','App\\Models\\Shared\\District','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"Gokwe South\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(602,'District','created','App\\Models\\Shared\\District','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"Gweru\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(603,'District','created','App\\Models\\Shared\\District','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"Kwekwe\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(604,'District','created','App\\Models\\Shared\\District','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"Mberengwa\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(605,'District','created','App\\Models\\Shared\\District','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"Shurugwi\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(606,'District','created','App\\Models\\Shared\\District','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"Zvishavane\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(607,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Person\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(608,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Company\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(609,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Church\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(610,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Other Organization\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(611,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Divorced\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(612,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Engaged\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(613,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Married\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(614,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Single\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(615,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Widowed\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(616,'AddressType','created','App\\Models\\Shared\\AddressType','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"business\", \"title\": \"Business\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(617,'AddressType','created','App\\Models\\Shared\\AddressType','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"complex\", \"title\": \"Complex\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(618,'AddressType','created','App\\Models\\Shared\\AddressType','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"home\", \"title\": \"Home\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(619,'AddressType','created','App\\Models\\Shared\\AddressType','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"physical\", \"title\": \"Physical\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(620,'AddressType','created','App\\Models\\Shared\\AddressType','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"postal\", \"title\": \"Postal\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(621,'Course','created','App\\Models\\Institution\\Course','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Beauty Therapy\", \"slug\": \"beauty-therapy\", \"position\": 1, \"description\": \"Applied Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(622,'Course','created','App\\Models\\Institution\\Course','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Cosmetology\", \"slug\": \"cosmetology\", \"position\": 2, \"description\": \"Applied Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(623,'Course','created','App\\Models\\Institution\\Course','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Hairdressing\", \"slug\": \"hairdressing\", \"position\": 3, \"description\": \"Applied Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(624,'Course','created','App\\Models\\Institution\\Course','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Industrial Clothing Design and Construction Design\", \"slug\": \"industrial-clothing-design-and-construction-design\", \"position\": 4, \"description\": \"Applied Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(625,'Course','created','App\\Models\\Institution\\Course','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Biological Technology\", \"slug\": \"applied-biological-technology\", \"position\": 5, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(626,'Course','created','App\\Models\\Institution\\Course','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Chemical Technology\", \"slug\": \"applied-chemical-technology\", \"position\": 6, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(627,'Course','created','App\\Models\\Institution\\Course','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Chemical Engineering\", \"slug\": \"chemical-engineering\", \"position\": 7, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(628,'Course','created','App\\Models\\Institution\\Course','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Chemical Technology\", \"slug\": \"chemical-technology\", \"position\": 8, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(629,'Course','created','App\\Models\\Institution\\Course','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Food Science\", \"slug\": \"food-science\", \"position\": 9, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(630,'Course','created','App\\Models\\Institution\\Course','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Horticulture\", \"slug\": \"horticulture\", \"position\": 10, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(631,'Course','created','App\\Models\\Institution\\Course','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Laboratory Technology\", \"slug\": \"laboratory-technology\", \"position\": 11, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(632,'Course','created','App\\Models\\Institution\\Course','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Metallurgical Assaying\", \"slug\": \"metallurgical-assaying\", \"position\": 12, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(633,'Course','created','App\\Models\\Institution\\Course','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Pharmaceutical Technology\", \"slug\": \"pharmaceutical-technology\", \"position\": 13, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(634,'Course','created','App\\Models\\Institution\\Course','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Polymer Technology\", \"slug\": \"polymer-technology\", \"position\": 14, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(635,'Course','created','App\\Models\\Institution\\Course','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Automobile Electrics And Electronics\", \"slug\": \"automobile-electrics-and-electronics\", \"position\": 15, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(636,'Course','created','App\\Models\\Institution\\Course','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Engineering\", \"slug\": \"automotive-engineering\", \"position\": 16, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(637,'Course','created','App\\Models\\Institution\\Course','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Precision Machining\", \"slug\": \"automotive-precision-machining\", \"position\": 17, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(638,'Course','created','App\\Models\\Institution\\Course','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Diesel Plant Fitting\", \"slug\": \"diesel-plant-fitting\", \"position\": 18, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(639,'Course','created','App\\Models\\Institution\\Course','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Cycle Machining\", \"slug\": \"motor-cycle-machining\", \"position\": 19, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(640,'Course','created','App\\Models\\Institution\\Course','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Vehicle Body Repairs\", \"slug\": \"motor-vehicle-body-repairs\", \"position\": 20, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(641,'Course','created','App\\Models\\Institution\\Course','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Vehicle Mechanics\", \"slug\": \"motor-vehicle-mechanics\", \"position\": 21, \"description\": \"Automotive Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(642,'Course','created','App\\Models\\Institution\\Course','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Accountancy\", \"slug\": \"accountancy\", \"position\": 25, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(643,'Course','created','App\\Models\\Institution\\Course','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Banking and Finance\", \"slug\": \"banking-and-finance\", \"position\": 26, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(644,'Course','created','App\\Models\\Institution\\Course','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Health Services Management\", \"slug\": \"health-services-management\", \"position\": 27, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(645,'Course','created','App\\Models\\Institution\\Course','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Human Resources Management\", \"slug\": \"human-resources-management\", \"position\": 28, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(646,'Course','created','App\\Models\\Institution\\Course','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Pensions And Investments Management\", \"slug\": \"pensions-and-investments-management\", \"position\": 29, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(647,'Course','created','App\\Models\\Institution\\Course','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Purchasing And Supply Management\", \"slug\": \"purchasing-and-supply-management\", \"position\": 30, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(648,'Course','created','App\\Models\\Institution\\Course','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Sales And Marketing Management\", \"slug\": \"sales-and-marketing-management\", \"position\": 31, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(649,'Course','created','App\\Models\\Institution\\Course','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Trainers Diploma In Education\", \"slug\": \"trainers-diploma-in-education\", \"position\": 33, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(650,'Course','created','App\\Models\\Institution\\Course','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Transport And Logistics Management\", \"slug\": \"transport-and-logistics-management\", \"position\": 32, \"description\": \"Business And Management Studies\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(651,'Course','created','App\\Models\\Institution\\Course','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Architectural Technology\", \"slug\": \"architectural-technology\", \"position\": 34, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(652,'Course','created','App\\Models\\Institution\\Course','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Cartography And Geo-Visualization Theory Technology\", \"slug\": \"cartography-and-geo-visualization-theory-technology\", \"position\": 35, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(653,'Course','created','App\\Models\\Institution\\Course','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"Civil Engineering\", \"slug\": \"civil-engineering\", \"position\": 36, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(654,'Course','created','App\\Models\\Institution\\Course','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"Quantity Surveying\", \"slug\": \"quantity-surveying\", \"position\": 37, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(655,'Course','created','App\\Models\\Institution\\Course','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"Surveying and Geomatics\", \"slug\": \"surveying-and-geomatics\", \"position\": 38, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(656,'Course','created','App\\Models\\Institution\\Course','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"Urban And Regional Planning\", \"slug\": \"urban-and-regional-planning\", \"position\": 39, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(657,'Course','created','App\\Models\\Institution\\Course','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"Valuation And Estate Management\", \"slug\": \"valuation-and-estate-management\", \"position\": 40, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(658,'Course','created','App\\Models\\Institution\\Course','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"Water Resources And Irrigation Engineering\", \"slug\": \"water-resources-and-irrigation-engineering\", \"position\": 41, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(659,'Course','created','App\\Models\\Institution\\Course','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"Building Technology\", \"slug\": \"building-technology\", \"position\": 42, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(660,'Course','created','App\\Models\\Institution\\Course','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"Carpentry and Joinery\", \"slug\": \"carpentry-and-joinery\", \"position\": 43, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(661,'Course','created','App\\Models\\Institution\\Course','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"Construction Engineering\", \"slug\": \"construction-engineering\", \"position\": 44, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(662,'Course','created','App\\Models\\Institution\\Course','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"Painting and Decorating Technology\", \"slug\": \"painting-and-decorating-technology\", \"position\": 45, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(663,'Course','created','App\\Models\\Institution\\Course','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"Plumbing and Drain Laying\", \"slug\": \"plumbing-and-drain-laying\", \"position\": 46, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(664,'Course','created','App\\Models\\Institution\\Course','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"Computer Systems\", \"slug\": \"computer-systems\", \"position\": 47, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(665,'Course','created','App\\Models\\Institution\\Course','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"Domestic and Industrial Solar Installation\", \"slug\": \"domestic-and-industrial-solar-installation\", \"position\": 48, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(666,'Course','created','App\\Models\\Institution\\Course','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"Electrical Power Engineering\", \"slug\": \"electrical-power-engineering\", \"position\": 50, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(667,'Course','created','App\\Models\\Institution\\Course','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"Electronic Communication Systems\", \"slug\": \"electronic-communication-systems\", \"position\": 49, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(668,'Course','created','App\\Models\\Institution\\Course','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"Instrumentation and Control Systems\", \"slug\": \"instrumentation-and-control-systems\", \"position\": 51, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(669,'Course','created','App\\Models\\Institution\\Course','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"Microwave and Radar\", \"slug\": \"microwave-and-radar\", \"position\": 52, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(670,'Course','created','App\\Models\\Institution\\Course','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"Mobile and Satellite Communication\", \"slug\": \"mobile-and-satellite-communication\", \"position\": 53, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(671,'Course','created','App\\Models\\Institution\\Course','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"Information Technology\", \"slug\": \"information-technology\", \"position\": 22, \"description\": \"Information Communication Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(672,'Course','created','App\\Models\\Institution\\Course','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Computer Engineering\", \"slug\": \"professional-computer-engineering\", \"position\": 23, \"description\": \"Information Communication Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(673,'Course','created','App\\Models\\Institution\\Course','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Computing and Information Systems\", \"slug\": \"professional-computing-and-information-systems\", \"position\": 24, \"description\": \"Information Communication Technology\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(674,'Course','created','App\\Models\\Institution\\Course','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"Library and Information Sciences\", \"slug\": \"library-and-information-sciences\", \"position\": 54, \"description\": \"Library and Information Sciences\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(675,'Course','created','App\\Models\\Institution\\Course','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"Records Management and Information Sciences\", \"slug\": \"records-management-and-information-sciences\", \"position\": 55, \"description\": \"Library and Information Sciences\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(676,'Course','created','App\\Models\\Institution\\Course','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"Draughting and Design Technology\", \"slug\": \"draughting-and-design-technology\", \"position\": 56, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(677,'Course','created','App\\Models\\Institution\\Course','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"Fabrication Engineering\", \"slug\": \"fabrication-engineering\", \"position\": 57, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(678,'Course','created','App\\Models\\Institution\\Course','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"Machine Shop Engineering\", \"slug\": \"machine-shop-engineering\", \"position\": 58, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(679,'Course','created','App\\Models\\Institution\\Course','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"Mechanical Engineering\", \"slug\": \"mechanical-engineering\", \"position\": 59, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(680,'Course','created','App\\Models\\Institution\\Course','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"Millwright Works\", \"slug\": \"millwright-works\", \"position\": 60, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(681,'Course','created','App\\Models\\Institution\\Course','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"Plant Engineering\", \"slug\": \"plant-engineering\", \"position\": 61, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(682,'Course','created','App\\Models\\Institution\\Course','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"Production Engineering\", \"slug\": \"production-engineering\", \"position\": 62, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(683,'Course','created','App\\Models\\Institution\\Course','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"Refrigeration and Air Conditioning\", \"slug\": \"refrigeration-and-air-conditioning\", \"position\": 63, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(684,'Course','created','App\\Models\\Institution\\Course','created',64,NULL,NULL,'{\"attributes\": {\"name\": \"Vehicle Body Building\", \"slug\": \"vehicle-body-building\", \"position\": 64, \"description\": \"Mechanical And Production Engineering\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(685,'Course','created','App\\Models\\Institution\\Course','created',65,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Art and Design\", \"slug\": \"applied-art-and-design\", \"position\": 65, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(686,'Course','created','App\\Models\\Institution\\Course','created',66,NULL,NULL,'{\"attributes\": {\"name\": \"Design For Print\", \"slug\": \"design-for-print\", \"position\": 66, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(687,'Course','created','App\\Models\\Institution\\Course','created',67,NULL,NULL,'{\"attributes\": {\"name\": \"Fine Arts\", \"slug\": \"fine-arts\", \"position\": 67, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(688,'Course','created','App\\Models\\Institution\\Course','created',68,NULL,NULL,'{\"attributes\": {\"name\": \"Machine Printing\", \"slug\": \"machine-printing\", \"position\": 68, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(689,'Course','created','App\\Models\\Institution\\Course','created',69,NULL,NULL,'{\"attributes\": {\"name\": \"Multimedia\", \"slug\": \"multimedia\", \"position\": 69, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(690,'Course','created','App\\Models\\Institution\\Course','created',70,NULL,NULL,'{\"attributes\": {\"name\": \"Packaging Machine Minding\", \"slug\": \"packaging-machine-minding\", \"position\": 70, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(691,'Course','created','App\\Models\\Institution\\Course','created',71,NULL,NULL,'{\"attributes\": {\"name\": \"Photography\", \"slug\": \"photography\", \"position\": 71, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(692,'Course','created','App\\Models\\Institution\\Course','created',72,NULL,NULL,'{\"attributes\": {\"name\": \"Printing, Finishing and Converting\", \"slug\": \"printing-finishing-and-converting\", \"position\": 72, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(693,'Course','created','App\\Models\\Institution\\Course','created',73,NULL,NULL,'{\"attributes\": {\"name\": \"Print Finishing Technology\", \"slug\": \"print-finishing-technology\", \"position\": 73, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(694,'Course','created','App\\Models\\Institution\\Course','created',74,NULL,NULL,'{\"attributes\": {\"name\": \"Print Production Technology\", \"slug\": \"print-production-technology\", \"position\": 75, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(695,'Course','created','App\\Models\\Institution\\Course','created',75,NULL,NULL,'{\"attributes\": {\"name\": \"Print Origination Technology\", \"slug\": \"print-origination-technology\", \"position\": 74, \"description\": \"Printing And Graphic Arts\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(696,'Course','created','App\\Models\\Institution\\Course','created',76,NULL,NULL,'{\"attributes\": {\"name\": \"Broadcast Journalism\", \"slug\": \"broadcast-journalism\", \"position\": 76, \"description\": \"Mass Communication\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(697,'Course','created','App\\Models\\Institution\\Course','created',77,NULL,NULL,'{\"attributes\": {\"name\": \"Mass Communication\", \"slug\": \"mass-communication\", \"position\": 77, \"description\": \"Mass Communication\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(698,'Course','created','App\\Models\\Institution\\Course','created',78,NULL,NULL,'{\"attributes\": {\"name\": \"Print Journalism\", \"slug\": \"print-journalism\", \"position\": 78, \"description\": \"Mass Communication\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(699,'Course','created','App\\Models\\Institution\\Course','created',79,NULL,NULL,'{\"attributes\": {\"name\": \"Public Relations\", \"slug\": \"public-relations\", \"position\": 79, \"description\": \"Mass Communication\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(700,'Course','created','App\\Models\\Institution\\Course','created',80,NULL,NULL,'{\"attributes\": {\"name\": \"Office Management\", \"slug\": \"office-management\", \"position\": 80, \"description\": \"Office Management\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(701,'Course','created','App\\Models\\Institution\\Course','created',81,NULL,NULL,'{\"attributes\": {\"name\": \"Bakery Technology and Management\", \"slug\": \"bakery-technology-and-management\", \"position\": 81, \"description\": \"Tourism And Hospitality\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(702,'Course','created','App\\Models\\Institution\\Course','created',82,NULL,NULL,'{\"attributes\": {\"name\": \"Culinary Arts\", \"slug\": \"culinary-arts\", \"position\": 82, \"description\": \"Tourism And Hospitality\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(703,'Course','created','App\\Models\\Institution\\Course','created',83,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Cookery\", \"slug\": \"professional-cookery\", \"position\": 84, \"description\": \"Tourism And Hospitality\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(704,'Course','created','App\\Models\\Institution\\Course','created',84,NULL,NULL,'{\"attributes\": {\"name\": \"Tourism and Hospitality Management\", \"slug\": \"tourism-and-hospitality-management\", \"position\": 83, \"description\": \"Tourism And Hospitality\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(705,'Department','created','App\\Models\\Institution\\Department','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Administration\", \"slug\": \"administration\", \"position\": 1, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(706,'Department','created','App\\Models\\Institution\\Department','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Arts\", \"slug\": \"applied-arts\", \"position\": 2, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(707,'Department','created','App\\Models\\Institution\\Department','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Engineering\", \"slug\": \"automotive-engineering\", \"position\": 3, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(708,'Department','created','App\\Models\\Institution\\Department','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Business And Management Studies\", \"slug\": \"business-and-management-studies\", \"position\": 4, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(709,'Department','created','App\\Models\\Institution\\Department','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Civil Engineering\", \"slug\": \"civil-engineering\", \"position\": 5, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(710,'Department','created','App\\Models\\Institution\\Department','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Clinic\", \"slug\": \"clinic\", \"position\": 6, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(711,'Department','created','App\\Models\\Institution\\Department','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Construction Engineering\", \"slug\": \"construction-engineering\", \"position\": 7, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(712,'Department','created','App\\Models\\Institution\\Department','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Dean Of Students\", \"slug\": \"dean-of-students\", \"position\": 8, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(713,'Department','created','App\\Models\\Institution\\Department','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Electrical Engineering\", \"slug\": \"electrical-engineering\", \"position\": 9, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(714,'Department','created','App\\Models\\Institution\\Department','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Executive\", \"slug\": \"executive\", \"position\": 10, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(715,'Department','created','App\\Models\\Institution\\Department','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Finance\", \"slug\": \"finance\", \"position\": 11, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(716,'Department','created','App\\Models\\Institution\\Department','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Human Resources\", \"slug\": \"human-resources\", \"position\": 12, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(717,'Department','created','App\\Models\\Institution\\Department','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Information Communication Technology\", \"slug\": \"information-communication-technology\", \"position\": 13, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(718,'Department','created','App\\Models\\Institution\\Department','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"IT Unit\", \"slug\": \"it-unit\", \"position\": 14, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(719,'Department','created','App\\Models\\Institution\\Department','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Library And Information Sciences\", \"slug\": \"library-and-information-sciences\", \"position\": 15, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(720,'Department','created','App\\Models\\Institution\\Department','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Mass Communication\", \"slug\": \"mass-communication\", \"position\": 16, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(721,'Department','created','App\\Models\\Institution\\Department','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Mechanical And Production Engineering\", \"slug\": \"mechanical-and-production-engineering\", \"position\": 17, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(722,'Department','created','App\\Models\\Institution\\Department','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Office Management\", \"slug\": \"office-management\", \"position\": 18, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(723,'Department','created','App\\Models\\Institution\\Department','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Printing And Graphics Arts\", \"slug\": \"printing-and-graphics-arts\", \"position\": 19, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(724,'Department','created','App\\Models\\Institution\\Department','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Procurement Management Unit\", \"slug\": \"procurement-management-unit\", \"position\": 20, \"description\": null, \"is_academic\": 0}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(725,'Department','created','App\\Models\\Institution\\Department','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Science Technology\", \"slug\": \"science-technology\", \"position\": 21, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(726,'Department','created','App\\Models\\Institution\\Department','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Tourism And Hospitality\", \"slug\": \"tourism-and-hospitality\", \"position\": 22, \"description\": null, \"is_academic\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(727,'Division','created','App\\Models\\Institution\\Division','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Business\", \"position\": 1, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(728,'Division','created','App\\Models\\Institution\\Division','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Management\", \"position\": 2, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(729,'Division','created','App\\Models\\Institution\\Division','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Pedagogics\", \"position\": 3, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(730,'Grade','created','App\\Models\\Institution\\Grade','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"A\", \"position\": 1, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(731,'Grade','created','App\\Models\\Institution\\Grade','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"B\", \"position\": 2, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(732,'Grade','created','App\\Models\\Institution\\Grade','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"C\", \"position\": 3, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(733,'Grade','created','App\\Models\\Institution\\Grade','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"D\", \"position\": 4, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(734,'Grade','created','App\\Models\\Institution\\Grade','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"E\", \"position\": 5, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(735,'Grade','created','App\\Models\\Institution\\Grade','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"U\", \"position\": 6, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(736,'Level','created','App\\Models\\Institution\\Level','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 3\", \"position\": 1, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(737,'Level','created','App\\Models\\Institution\\Level','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 4\", \"position\": 2, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(738,'Level','created','App\\Models\\Institution\\Level','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 5\", \"position\": 3, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(739,'Level','created','App\\Models\\Institution\\Level','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 6\", \"position\": 4, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(740,'Level','created','App\\Models\\Institution\\Level','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"NC\", \"position\": 5, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(741,'Level','created','App\\Models\\Institution\\Level','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"ND\", \"position\": 6, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(742,'Level','created','App\\Models\\Institution\\Level','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"HND\", \"position\": 7, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(743,'Level','created','App\\Models\\Institution\\Level','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"BTECH\", \"position\": 8, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(744,'Level','created','App\\Models\\Institution\\Level','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"SDP\", \"position\": 9, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(745,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Primary school\", \"position\": 1, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(746,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Secondary school\", \"position\": 2, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(747,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Advanced Level\", \"position\": 3, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(748,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Tertiary Level\", \"position\": 4, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(749,'Relationship','created','App\\Models\\Shared\\Relationship','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Parent\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(750,'Relationship','created','App\\Models\\Shared\\Relationship','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Spouse\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(751,'Relationship','created','App\\Models\\Shared\\Relationship','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Guardian\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(752,'Subject','created','App\\Models\\Institution\\Subject','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Accounts\", \"position\": 1, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(753,'Subject','created','App\\Models\\Institution\\Subject','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Agriculture\", \"position\": 2, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(754,'Subject','created','App\\Models\\Institution\\Subject','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Art\", \"position\": 3, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(755,'Subject','created','App\\Models\\Institution\\Subject','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Bible Knowledge\", \"position\": 4, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(756,'Subject','created','App\\Models\\Institution\\Subject','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Building Studies\", \"position\": 5, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(757,'Subject','created','App\\Models\\Institution\\Subject','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Business and Enterprise Skills\", \"position\": 6, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(758,'Subject','created','App\\Models\\Institution\\Subject','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Business Studies\", \"position\": 7, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(759,'Subject','created','App\\Models\\Institution\\Subject','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Chinese\", \"position\": 8, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(760,'Subject','created','App\\Models\\Institution\\Subject','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Commerce\", \"position\": 9, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(761,'Subject','created','App\\Models\\Institution\\Subject','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Computer Science\", \"position\": 10, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(762,'Subject','created','App\\Models\\Institution\\Subject','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Design and Technology\", \"position\": 11, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(763,'Subject','created','App\\Models\\Institution\\Subject','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Economics\", \"position\": 12, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(764,'Subject','created','App\\Models\\Institution\\Subject','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"English\", \"position\": 13, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(765,'Subject','created','App\\Models\\Institution\\Subject','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Fashion and Fabrics\", \"position\": 14, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(766,'Subject','created','App\\Models\\Institution\\Subject','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Food and Nutrition\", \"position\": 15, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(767,'Subject','created','App\\Models\\Institution\\Subject','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"French\", \"position\": 16, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(768,'Subject','created','App\\Models\\Institution\\Subject','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Geography\", \"position\": 17, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(769,'Subject','created','App\\Models\\Institution\\Subject','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"German\", \"position\": 18, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(770,'Subject','created','App\\Models\\Institution\\Subject','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"History\", \"position\": 19, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(771,'Subject','created','App\\Models\\Institution\\Subject','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Integrated Science\", \"position\": 20, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(772,'Subject','created','App\\Models\\Institution\\Subject','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Literature in English\", \"position\": 21, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(773,'Subject','created','App\\Models\\Institution\\Subject','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Mathematics\", \"position\": 22, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(774,'Subject','created','App\\Models\\Institution\\Subject','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Metal Technology and Design\", \"position\": 23, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(775,'Subject','created','App\\Models\\Institution\\Subject','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Music\", \"position\": 24, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(776,'Subject','created','App\\Models\\Institution\\Subject','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Ndebele\", \"position\": 25, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(777,'Subject','created','App\\Models\\Institution\\Subject','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Physical Education, Sport and Mass Displays\", \"position\": 26, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(778,'Subject','created','App\\Models\\Institution\\Subject','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Religious Studies\", \"position\": 27, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(779,'Subject','created','App\\Models\\Institution\\Subject','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Shona\", \"position\": 28, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(780,'Subject','created','App\\Models\\Institution\\Subject','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Spanish\", \"position\": 29, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(781,'Subject','created','App\\Models\\Institution\\Subject','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Technical Graphics\", \"position\": 30, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(782,'Subject','created','App\\Models\\Institution\\Subject','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Wood Technology and Design\", \"position\": 31, \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(783,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Full Time\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(784,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Part Time\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(785,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Block Release\", \"description\": null}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(786,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',1,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 1}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(787,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',2,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 2}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(788,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',3,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 3}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(789,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',4,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 4}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(790,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',5,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 5}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(791,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',6,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 6}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(792,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',7,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 7}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(793,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',8,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 8}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(794,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',9,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 9}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(795,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',10,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 10}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(796,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',11,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 11}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(797,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',12,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 12}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(798,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',13,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 13}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(799,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',14,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 14}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(800,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',15,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 15}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(801,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',16,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 16}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(802,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',17,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 17}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(803,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',18,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 18}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(804,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',19,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 19}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(805,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',20,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 20}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(806,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',21,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 21}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(807,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',22,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 22}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(808,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Full time\", \"description\": \"Full-time employment (35–40+ hours/week with benefits)\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(809,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Part time\", \"description\": \"Part-time employment (less than 35 hours/week)\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(810,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Temporary\", \"description\": \"Temporary or contract-based employment\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(811,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Freelance\", \"description\": \"Freelance or self-employed contractor work\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(812,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Intern\", \"description\": \"Internship or apprenticeship (temporary, for experience)\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(813,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Casual\", \"description\": \"Casual work (on-call or irregular hours)\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(814,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Seasonal\", \"description\": \"Seasonal employment (e.g. holiday or harvest periods)\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(815,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Remote\", \"description\": \"Remote or telecommuting work (offsite)\"}}',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14'),(816,'User','created','App\\Models\\Users\\User','created',2,NULL,NULL,'{\"attributes\": {\"email\": \"jimmyneds@gmail.com\", \"password\": \"$2y$12$nl/94Yxx8B9vwLuVP7PWsupHnNgCk8GgKMWDzbNxTrKS1k03/isTy\", \"avatar_id\": null, \"last_name\": \"Gudhlanga\", \"status_id\": 1, \"tenant_id\": 1, \"first_name\": \"James\", \"login_count\": 0, \"middle_name\": \"Jimmy\", \"phone_number\": null, \"last_login_at\": null, \"email_verified_at\": null}}',NULL,'2025-07-16 06:40:09','2025-07-16 06:40:09'),(817,'User','updated','App\\Models\\Users\\User','updated',2,'App\\Models\\Users\\User',2,'{\"old\": {\"login_count\": 0, \"last_login_at\": null}, \"attributes\": {\"login_count\": 1, \"last_login_at\": \"2025-07-16 08:40:09\"}}',NULL,'2025-07-16 06:40:09','2025-07-16 06:40:09'),(818,'User','updated','App\\Models\\Users\\User','updated',2,'App\\Models\\Users\\User',2,'{\"old\": {\"email_verified_at\": null}, \"attributes\": {\"email_verified_at\": \"2025-07-16T06:40:39.000000Z\"}}',NULL,'2025-07-16 06:40:39','2025-07-16 06:40:39'),(819,'User','updated','App\\Models\\Users\\User','updated',2,'App\\Models\\Users\\User',2,'{\"old\": {\"login_count\": 1, \"last_login_at\": \"2025-07-16 08:40:09\"}, \"attributes\": {\"login_count\": 2, \"last_login_at\": \"2025-07-16 13:21:15\"}}',NULL,'2025-07-16 11:21:15','2025-07-16 11:21:15');
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
INSERT INTO `address_types` VALUES (1,'Business','business',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Complex','complex',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Home','home',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Physical','physical',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Postal','postal',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `cache` VALUES ('hrepoly_cache_da4b9237bacccdf19c0760cab7aec4a8359010b0','i:1;',1752648099),('hrepoly_cache_da4b9237bacccdf19c0760cab7aec4a8359010b0:timer','i:1752648099;',1752648099),('hrepoly_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:8:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:11:\"description\";s:1:\"d\";s:10:\"guard_name\";s:1:\"e\";s:9:\"module_id\";s:1:\"r\";s:5:\"roles\";s:1:\"j\";s:4:\"slug\";s:1:\"k\";s:13:\"role_group_id\";}s:11:\"permissions\";a:193:{i:0;a:6:{s:1:\"a\";i:1;s:1:\"b\";s:17:\"view:acl-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:1;a:6:{s:1:\"a\";i:2;s:1:\"b\";s:15:\"viewAny:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:2;a:6:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"view:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:3;a:6:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"create:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:6:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"update:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:6:{s:1:\"a\";i:6;s:1:\"b\";s:14:\"delete:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:6:{s:1:\"a\";i:7;s:1:\"b\";s:15:\"restore:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:7;a:6:{s:1:\"a\";i:8;s:1:\"b\";s:19:\"forceDelete:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:8;a:6:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"import:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:9;a:6:{s:1:\"a\";i:10;s:1:\"b\";s:14:\"export:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:10;a:6:{s:1:\"a\";i:11;s:1:\"b\";s:22:\"viewAuditTrail:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:11;a:6:{s:1:\"a\";i:12;s:1:\"b\";s:13:\"viewAny:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:12;a:6:{s:1:\"a\";i:13;s:1:\"b\";s:10:\"view:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:13;a:6:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"create:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:14;a:6:{s:1:\"a\";i:15;s:1:\"b\";s:12:\"update:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:15;a:6:{s:1:\"a\";i:16;s:1:\"b\";s:12:\"delete:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:16;a:6:{s:1:\"a\";i:17;s:1:\"b\";s:13:\"restore:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:17;a:6:{s:1:\"a\";i:18;s:1:\"b\";s:17:\"forceDelete:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:18;a:6:{s:1:\"a\";i:19;s:1:\"b\";s:12:\"import:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:19;a:6:{s:1:\"a\";i:20;s:1:\"b\";s:12:\"export:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:20;a:6:{s:1:\"a\";i:21;s:1:\"b\";s:20:\"viewAuditTrail:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:21;a:6:{s:1:\"a\";i:22;s:1:\"b\";s:19:\"viewAny:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:22;a:6:{s:1:\"a\";i:23;s:1:\"b\";s:16:\"view:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:23;a:6:{s:1:\"a\";i:24;s:1:\"b\";s:18:\"create:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:24;a:6:{s:1:\"a\";i:25;s:1:\"b\";s:18:\"update:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:25;a:6:{s:1:\"a\";i:26;s:1:\"b\";s:18:\"delete:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:26;a:6:{s:1:\"a\";i:27;s:1:\"b\";s:19:\"restore:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:27;a:6:{s:1:\"a\";i:28;s:1:\"b\";s:23:\"forceDelete:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:28;a:6:{s:1:\"a\";i:29;s:1:\"b\";s:18:\"import:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:29;a:6:{s:1:\"a\";i:30;s:1:\"b\";s:18:\"export:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:30;a:6:{s:1:\"a\";i:31;s:1:\"b\";s:26:\"viewAuditTrail:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:2;}}i:31;a:6:{s:1:\"a\";i:32;s:1:\"b\";s:22:\"viewAny:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:32;a:6:{s:1:\"a\";i:33;s:1:\"b\";s:19:\"view:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:33;a:6:{s:1:\"a\";i:34;s:1:\"b\";s:21:\"create:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:34;a:6:{s:1:\"a\";i:35;s:1:\"b\";s:21:\"update:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:35;a:6:{s:1:\"a\";i:36;s:1:\"b\";s:21:\"delete:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:36;a:6:{s:1:\"a\";i:37;s:1:\"b\";s:22:\"restore:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:37;a:6:{s:1:\"a\";i:38;s:1:\"b\";s:26:\"forceDelete:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:38;a:6:{s:1:\"a\";i:39;s:1:\"b\";s:21:\"import:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:39;a:6:{s:1:\"a\";i:40;s:1:\"b\";s:21:\"export:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:40;a:6:{s:1:\"a\";i:41;s:1:\"b\";s:28:\"crud-settings:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:41;a:6:{s:1:\"a\";i:42;s:1:\"b\";s:29:\"viewAuditTrail:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:2;}}i:42;a:6:{s:1:\"a\";i:43;s:1:\"b\";s:18:\"viewAny:dashboards\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:4;s:1:\"r\";a:1:{i:0;i:2;}}i:43;a:6:{s:1:\"a\";i:44;s:1:\"b\";s:15:\"view:dashboards\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:4;s:1:\"r\";a:1:{i:0;i:2;}}i:44;a:6:{s:1:\"a\";i:45;s:1:\"b\";s:15:\"viewAny:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:45;a:6:{s:1:\"a\";i:46;s:1:\"b\";s:12:\"view:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:46;a:6:{s:1:\"a\";i:47;s:1:\"b\";s:14:\"create:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:47;a:6:{s:1:\"a\";i:48;s:1:\"b\";s:14:\"update:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:48;a:6:{s:1:\"a\";i:49;s:1:\"b\";s:14:\"delete:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:49;a:6:{s:1:\"a\";i:50;s:1:\"b\";s:15:\"restore:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:50;a:6:{s:1:\"a\";i:51;s:1:\"b\";s:19:\"forceDelete:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:51;a:6:{s:1:\"a\";i:52;s:1:\"b\";s:14:\"import:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:52;a:6:{s:1:\"a\";i:53;s:1:\"b\";s:14:\"export:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:2;}}i:53;a:6:{s:1:\"a\";i:54;s:1:\"b\";s:15:\"viewAny:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:54;a:6:{s:1:\"a\";i:55;s:1:\"b\";s:12:\"view:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:55;a:6:{s:1:\"a\";i:56;s:1:\"b\";s:14:\"create:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:56;a:6:{s:1:\"a\";i:57;s:1:\"b\";s:14:\"update:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:57;a:6:{s:1:\"a\";i:58;s:1:\"b\";s:14:\"delete:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:58;a:6:{s:1:\"a\";i:59;s:1:\"b\";s:15:\"restore:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:59;a:6:{s:1:\"a\";i:60;s:1:\"b\";s:19:\"forceDelete:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:60;a:6:{s:1:\"a\";i:61;s:1:\"b\";s:14:\"import:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:61;a:6:{s:1:\"a\";i:62;s:1:\"b\";s:14:\"export:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:62;a:6:{s:1:\"a\";i:63;s:1:\"b\";s:21:\"crud-settings:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:63;a:6:{s:1:\"a\";i:64;s:1:\"b\";s:22:\"viewAuditTrail:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:2;}}i:64;a:5:{s:1:\"a\";i:65;s:1:\"b\";s:21:\"manageOwnData:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;}i:65;a:6:{s:1:\"a\";i:66;s:1:\"b\";s:13:\"viewAny:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:66;a:6:{s:1:\"a\";i:67;s:1:\"b\";s:10:\"view:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:67;a:6:{s:1:\"a\";i:68;s:1:\"b\";s:12:\"create:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:68;a:6:{s:1:\"a\";i:69;s:1:\"b\";s:12:\"update:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:69;a:6:{s:1:\"a\";i:70;s:1:\"b\";s:12:\"delete:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:70;a:6:{s:1:\"a\";i:71;s:1:\"b\";s:13:\"restore:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:71;a:6:{s:1:\"a\";i:72;s:1:\"b\";s:17:\"forceDelete:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:72;a:6:{s:1:\"a\";i:73;s:1:\"b\";s:12:\"import:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:73;a:6:{s:1:\"a\";i:74;s:1:\"b\";s:12:\"export:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:74;a:6:{s:1:\"a\";i:75;s:1:\"b\";s:19:\"crud-settings:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:75;a:6:{s:1:\"a\";i:76;s:1:\"b\";s:20:\"viewAuditTrail:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:2;}}i:76;a:6:{s:1:\"a\";i:77;s:1:\"b\";s:11:\"root:manage\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:10;s:1:\"r\";a:1:{i:0;i:2;}}i:77;a:6:{s:1:\"a\";i:78;s:1:\"b\";s:13:\"view:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:78;a:6:{s:1:\"a\";i:79;s:1:\"b\";s:15:\"create:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:79;a:6:{s:1:\"a\";i:80;s:1:\"b\";s:15:\"update:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:80;a:6:{s:1:\"a\";i:81;s:1:\"b\";s:15:\"delete:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:81;a:6:{s:1:\"a\";i:82;s:1:\"b\";s:16:\"restore:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:82;a:6:{s:1:\"a\";i:83;s:1:\"b\";s:20:\"forceDelete:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:83;a:6:{s:1:\"a\";i:84;s:1:\"b\";s:15:\"import:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:84;a:6:{s:1:\"a\";i:85;s:1:\"b\";s:15:\"export:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:85;a:6:{s:1:\"a\";i:86;s:1:\"b\";s:23:\"viewAuditTrail:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:86;a:6:{s:1:\"a\";i:87;s:1:\"b\";s:25:\"view:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:87;a:6:{s:1:\"a\";i:88;s:1:\"b\";s:27:\"create:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:88;a:6:{s:1:\"a\";i:89;s:1:\"b\";s:27:\"update:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:89;a:6:{s:1:\"a\";i:90;s:1:\"b\";s:27:\"delete:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:90;a:6:{s:1:\"a\";i:91;s:1:\"b\";s:28:\"restore:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:91;a:6:{s:1:\"a\";i:92;s:1:\"b\";s:32:\"forceDelete:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:92;a:6:{s:1:\"a\";i:93;s:1:\"b\";s:27:\"import:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:93;a:6:{s:1:\"a\";i:94;s:1:\"b\";s:27:\"export:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:94;a:6:{s:1:\"a\";i:95;s:1:\"b\";s:35:\"viewAuditTrail:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:2;}}i:95;a:6:{s:1:\"a\";i:96;s:1:\"b\";s:27:\"viewAny:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:96;a:6:{s:1:\"a\";i:97;s:1:\"b\";s:24:\"view:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:97;a:6:{s:1:\"a\";i:98;s:1:\"b\";s:26:\"create:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:98;a:6:{s:1:\"a\";i:99;s:1:\"b\";s:26:\"update:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:99;a:6:{s:1:\"a\";i:100;s:1:\"b\";s:26:\"delete:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:100;a:6:{s:1:\"a\";i:101;s:1:\"b\";s:27:\"restore:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:101;a:6:{s:1:\"a\";i:102;s:1:\"b\";s:31:\"forceDelete:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:102;a:6:{s:1:\"a\";i:103;s:1:\"b\";s:26:\"import:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:103;a:6:{s:1:\"a\";i:104;s:1:\"b\";s:26:\"export:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:104;a:6:{s:1:\"a\";i:105;s:1:\"b\";s:34:\"viewAuditTrail:department-metadata\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:2;}}i:105;a:6:{s:1:\"a\";i:106;s:1:\"b\";s:20:\"viewAny:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:106;a:6:{s:1:\"a\";i:107;s:1:\"b\";s:17:\"view:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:107;a:6:{s:1:\"a\";i:108;s:1:\"b\";s:19:\"create:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:108;a:6:{s:1:\"a\";i:109;s:1:\"b\";s:19:\"update:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:109;a:6:{s:1:\"a\";i:110;s:1:\"b\";s:19:\"delete:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:110;a:6:{s:1:\"a\";i:111;s:1:\"b\";s:20:\"restore:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:111;a:6:{s:1:\"a\";i:112;s:1:\"b\";s:24:\"forceDelete:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:112;a:6:{s:1:\"a\";i:113;s:1:\"b\";s:19:\"import:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:113;a:6:{s:1:\"a\";i:114;s:1:\"b\";s:19:\"export:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:114;a:6:{s:1:\"a\";i:115;s:1:\"b\";s:17:\"viewAny:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:115;a:6:{s:1:\"a\";i:116;s:1:\"b\";s:14:\"view:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:116;a:6:{s:1:\"a\";i:117;s:1:\"b\";s:16:\"create:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:117;a:6:{s:1:\"a\";i:118;s:1:\"b\";s:16:\"update:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:118;a:6:{s:1:\"a\";i:119;s:1:\"b\";s:16:\"delete:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:119;a:6:{s:1:\"a\";i:120;s:1:\"b\";s:17:\"restore:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:120;a:6:{s:1:\"a\";i:121;s:1:\"b\";s:21:\"forceDelete:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:121;a:6:{s:1:\"a\";i:122;s:1:\"b\";s:16:\"import:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:122;a:6:{s:1:\"a\";i:123;s:1:\"b\";s:16:\"export:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:123;a:6:{s:1:\"a\";i:124;s:1:\"b\";s:16:\"viewAny:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:124;a:6:{s:1:\"a\";i:125;s:1:\"b\";s:13:\"view:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:125;a:6:{s:1:\"a\";i:126;s:1:\"b\";s:15:\"create:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:126;a:6:{s:1:\"a\";i:127;s:1:\"b\";s:15:\"update:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:127;a:6:{s:1:\"a\";i:128;s:1:\"b\";s:15:\"delete:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:128;a:6:{s:1:\"a\";i:129;s:1:\"b\";s:16:\"restore:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:129;a:6:{s:1:\"a\";i:130;s:1:\"b\";s:20:\"forceDelete:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:130;a:6:{s:1:\"a\";i:131;s:1:\"b\";s:15:\"import:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:131;a:6:{s:1:\"a\";i:132;s:1:\"b\";s:15:\"export:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:132;a:6:{s:1:\"a\";i:133;s:1:\"b\";s:20:\"viewAny:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:133;a:6:{s:1:\"a\";i:134;s:1:\"b\";s:17:\"view:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:27;}}i:134;a:6:{s:1:\"a\";i:135;s:1:\"b\";s:19:\"create:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:27;}}i:135;a:6:{s:1:\"a\";i:136;s:1:\"b\";s:19:\"update:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:27;}}i:136;a:6:{s:1:\"a\";i:137;s:1:\"b\";s:19:\"delete:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:27;}}i:137;a:6:{s:1:\"a\";i:138;s:1:\"b\";s:20:\"restore:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:138;a:6:{s:1:\"a\";i:139;s:1:\"b\";s:24:\"forceDelete:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:27;}}i:139;a:6:{s:1:\"a\";i:140;s:1:\"b\";s:19:\"import:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:140;a:6:{s:1:\"a\";i:141;s:1:\"b\";s:19:\"export:next-of-kins\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:2;}}i:141;a:6:{s:1:\"a\";i:142;s:1:\"b\";s:25:\"viewOwnDashboard:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:142;a:6:{s:1:\"a\";i:143;s:1:\"b\";s:40:\"manageOwnStudentPersonalDetails:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:143;a:6:{s:1:\"a\";i:144;s:1:\"b\";s:39:\"manageOwnStudentProgramDetails:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:144;a:6:{s:1:\"a\";i:145;s:1:\"b\";s:39:\"manageOwnStudentSponsorDetails:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:145;a:6:{s:1:\"a\";i:146;s:1:\"b\";s:39:\"manageOwnStudentContactDetails:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:146;a:6:{s:1:\"a\";i:147;s:1:\"b\";s:41:\"manageOwnStudentFinancialDetails:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:147;a:6:{s:1:\"a\";i:148;s:1:\"b\";s:40:\"manageOwnStudentAcademicDetails:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:27;}}i:148;a:6:{s:1:\"a\";i:149;s:1:\"b\";s:27:\"manageStudentMetadata:admin\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:149;a:6:{s:1:\"a\";i:150;s:1:\"b\";s:16:\"viewAny:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:150;a:6:{s:1:\"a\";i:151;s:1:\"b\";s:13:\"view:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:151;a:6:{s:1:\"a\";i:152;s:1:\"b\";s:15:\"create:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:152;a:6:{s:1:\"a\";i:153;s:1:\"b\";s:15:\"update:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:153;a:6:{s:1:\"a\";i:154;s:1:\"b\";s:15:\"delete:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:154;a:6:{s:1:\"a\";i:155;s:1:\"b\";s:16:\"restore:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:155;a:6:{s:1:\"a\";i:156;s:1:\"b\";s:20:\"forceDelete:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:156;a:6:{s:1:\"a\";i:157;s:1:\"b\";s:15:\"import:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:157;a:6:{s:1:\"a\";i:158;s:1:\"b\";s:15:\"export:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:158;a:6:{s:1:\"a\";i:159;s:1:\"b\";s:22:\"crud-settings:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:159;a:6:{s:1:\"a\";i:160;s:1:\"b\";s:23:\"viewAuditTrail:students\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:13;s:1:\"r\";a:1:{i:0;i:2;}}i:160;a:6:{s:1:\"a\";i:161;s:1:\"b\";s:18:\"viewAny:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:161;a:6:{s:1:\"a\";i:162;s:1:\"b\";s:15:\"view:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:162;a:6:{s:1:\"a\";i:163;s:1:\"b\";s:17:\"create:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:163;a:6:{s:1:\"a\";i:164;s:1:\"b\";s:17:\"update:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:164;a:6:{s:1:\"a\";i:165;s:1:\"b\";s:17:\"delete:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:165;a:6:{s:1:\"a\";i:166;s:1:\"b\";s:18:\"restore:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:166;a:6:{s:1:\"a\";i:167;s:1:\"b\";s:22:\"forceDelete:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:167;a:6:{s:1:\"a\";i:168;s:1:\"b\";s:17:\"import:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:168;a:6:{s:1:\"a\";i:169;s:1:\"b\";s:17:\"export:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:169;a:6:{s:1:\"a\";i:170;s:1:\"b\";s:24:\"crud-settings:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:170;a:6:{s:1:\"a\";i:171;s:1:\"b\";s:25:\"viewAuditTrail:enrolments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:5;s:1:\"r\";a:1:{i:0;i:2;}}i:171;a:6:{s:1:\"a\";i:172;s:1:\"b\";s:20:\"viewAny:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:172;a:6:{s:1:\"a\";i:173;s:1:\"b\";s:17:\"view:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:173;a:6:{s:1:\"a\";i:174;s:1:\"b\";s:19:\"create:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:174;a:6:{s:1:\"a\";i:175;s:1:\"b\";s:19:\"update:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:175;a:6:{s:1:\"a\";i:176;s:1:\"b\";s:19:\"delete:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:176;a:6:{s:1:\"a\";i:177;s:1:\"b\";s:20:\"restore:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:177;a:6:{s:1:\"a\";i:178;s:1:\"b\";s:24:\"forceDelete:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:178;a:6:{s:1:\"a\";i:179;s:1:\"b\";s:19:\"import:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:179;a:6:{s:1:\"a\";i:180;s:1:\"b\";s:19:\"export:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:180;a:6:{s:1:\"a\";i:181;s:1:\"b\";s:26:\"crud-settings:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:181;a:6:{s:1:\"a\";i:182;s:1:\"b\";s:27:\"viewAuditTrail:examinations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:6;s:1:\"r\";a:1:{i:0;i:2;}}i:182;a:6:{s:1:\"a\";i:183;s:1:\"b\";s:22:\"viewAny:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:183;a:6:{s:1:\"a\";i:184;s:1:\"b\";s:19:\"view:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:184;a:6:{s:1:\"a\";i:185;s:1:\"b\";s:21:\"create:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:185;a:6:{s:1:\"a\";i:186;s:1:\"b\";s:21:\"update:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:186;a:6:{s:1:\"a\";i:187;s:1:\"b\";s:21:\"delete:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:187;a:6:{s:1:\"a\";i:188;s:1:\"b\";s:22:\"restore:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:188;a:6:{s:1:\"a\";i:189;s:1:\"b\";s:26:\"forceDelete:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:189;a:6:{s:1:\"a\";i:190;s:1:\"b\";s:21:\"import:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:190;a:6:{s:1:\"a\";i:191;s:1:\"b\";s:21:\"export:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:191;a:6:{s:1:\"a\";i:192;s:1:\"b\";s:28:\"crud-settings:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}i:192;a:6:{s:1:\"a\";i:193;s:1:\"b\";s:29:\"viewAuditTrail:accommodations\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:1;s:1:\"r\";a:1:{i:0;i:2;}}}s:5:\"roles\";a:2:{i:0;a:6:{s:1:\"a\";i:2;s:1:\"b\";s:19:\"Super Administrator\";s:1:\"j\";s:19:\"super-administrator\";s:1:\"k\";i:1;s:1:\"c\";s:48:\"Has unrestricted access to all system functions.\";s:1:\"d\";s:3:\"web\";}i:1;a:6:{s:1:\"a\";i:27;s:1:\"b\";s:7:\"Student\";s:1:\"j\";s:7:\"student\";s:1:\"k\";i:8;s:1:\"c\";s:36:\"Learner enrolled in the institution.\";s:1:\"d\";s:3:\"web\";}}}',1752734409);
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
INSERT INTO `communication_methods` VALUES (1,'Email','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,'Sms','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(3,'Phone','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL);
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
INSERT INTO `countries` VALUES (1,'Afghanistan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,'Albania',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(3,'Algeria',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(4,'Andorra',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(5,'Angola',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(6,'Antigua and Barbuda',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(7,'Argentina',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(8,'Armenia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(9,'Australia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(10,'Azerbaijan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(11,'Bahamas',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(12,'Bahrain',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(13,'Bangladesh',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(14,'Barbados',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(15,'Belgium',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(16,'Belize',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(17,'Benin',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(18,'Bhutan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(19,'Bolivia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(20,'Bosnia and Herzegovina',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(21,'Botswana',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(22,'Brazil',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(23,'Brunei',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(24,'Bulgaria',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(25,'Burkina Faso',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(26,'Burundi',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(27,'Chile',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(28,'Colombia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(29,'Comoros',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(30,'Costa Rica',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(31,'Democratic Republic of the Congo',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(32,'Denmark',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(33,'Djibouti',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(34,'Dominica',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(35,'Dominican Republic',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(36,'Germany',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(37,'Ecuador',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(38,'Egypt',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(39,'Equatorial Guinea',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(40,'El Salvador',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(41,'Eritrea',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(42,'Estonia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(43,'Ethiopia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(44,'Fiji',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(45,'Philippines',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(46,'Finland',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(47,'France',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(48,'Gabon',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(49,'Gambia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(50,'Georgia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(51,'Ghana',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(52,'Grenada',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(53,'Greece',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(54,'Guatemala',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(55,'Guinea',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(56,'Guinea-Bissau',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(57,'Guyana',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(58,'Haiti',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(59,'Honduras',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(60,'Hungary',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(61,'Ireland',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(62,'India',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(63,'Indonesia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(64,'Iran',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(65,'Iraq',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(66,'Israel',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(67,'Italy',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(68,'Côte d’Ivoire',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(69,'Jamaica',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(70,'Japan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(71,'Yemen',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(72,'Jordan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(73,'Cape Verde Islands',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(74,'Cambodia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(75,'Cameroon',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(76,'Canada',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(77,'Kazakhstan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(78,'Qatar',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(79,'Kenya',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(80,'Kyrgyzstan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(81,'Kiribati',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(82,'Kuwait',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(83,'Croatia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(84,'Cuba',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(85,'Laos',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(86,'Latvia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(87,'Lesotho',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(88,'Lebanon',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(89,'Liberia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(90,'Libya',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(91,'Liechtenstein',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(92,'Lithuania',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(93,'Luxembourg',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(94,'Madagascar',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(95,'Malawi',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(96,'Malaysia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(97,'Maldives',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(98,'Mali',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(99,'Malta',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(100,'Morocco',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(101,'Marshall Islands',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(102,'Mauritania',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(103,'Mauritius',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(104,'Mexico',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(105,'Myanmar (Burma)',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(106,'Micronesia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(107,'Moldova',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(108,'Monaco',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(109,'Mongolia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(110,'Montenegro',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(111,'Mozambique',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(112,'Namibia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(113,'Nauru',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(114,'Nepal',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(115,'Netherlands',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(116,'New Zealand',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(117,'Nicaragua',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(118,'Niger',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(119,'Nigeria',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(120,'North Korea',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(121,'Norway',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(122,'Ukraine',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(123,'Uzbekistan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(124,'Oman',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(125,'Austria',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(126,'East Timor',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(127,'Pakistan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(128,'Palau',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(129,'Panama',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(130,'Papua New Guinea',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(131,'Paraguay',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(132,'Peru',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(133,'Pole/Poland',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(134,'Portugal',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(135,'Republic of the Congo',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(136,'Macedonia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(137,'Romania',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(138,'Russia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(139,'Rwanda',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(140,'Samoa',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(141,'San Marino',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(142,'Saudi Arabia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(143,'São Tomé and Principe',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(144,'Senegal',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(145,'Central African Republic',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(146,'Serbia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(147,'Seychelles',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(148,'China',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(149,'Sierra Leone',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(150,'Singapore',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(151,'Cyprus',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(152,'Syria',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(153,'Slovakia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(154,'Slovenia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(155,'Sudan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(156,'Solomon Islands',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(157,'Somalia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(158,'Spain',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(159,'Sri Lanka',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(160,'Saint Kitts and Nevis',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(161,'St. Lucia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(162,'St. Vincent and the Grenadines',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(163,'South Africa',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(164,'Suid-Korea/South Korea',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(165,'South Sudan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(166,'Suriname',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(167,'Swaziland',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(168,'Sweden',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(169,'Switzerland',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(170,'Tajikistan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(171,'Taiwan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(172,'Tanzania',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(173,'Thailand',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(174,'Tonga',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(175,'Togo',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(176,'Trinidad and Tobago',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(177,'Chad',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(178,'Czech Republic',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(179,'Tunisia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(180,'Turkmenistan',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(181,'Turkey',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(182,'Tuvalu',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(183,'Uganda',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(184,'Uruguay',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(185,'Vanuatu',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(186,'Venezuela',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(187,'United Arab Emirates',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(188,'United Kingdom',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(189,'United States of America',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(190,'Vietnam',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(191,'Belarus',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(192,'Iceland',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(193,'Zambia',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(194,'Zimbabwe',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL);
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
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `courses` VALUES (1,'Beauty Therapy','beauty-therapy',1,'Applied Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Cosmetology','cosmetology',2,'Applied Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Hairdressing','hairdressing',3,'Applied Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Industrial Clothing Design and Construction Design','industrial-clothing-design-and-construction-design',4,'Applied Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Applied Biological Technology','applied-biological-technology',5,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'Applied Chemical Technology','applied-chemical-technology',6,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'Chemical Engineering','chemical-engineering',7,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'Chemical Technology','chemical-technology',8,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'Food Science','food-science',9,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,'Horticulture','horticulture',10,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,'Laboratory Technology','laboratory-technology',11,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(12,'Metallurgical Assaying','metallurgical-assaying',12,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(13,'Pharmaceutical Technology','pharmaceutical-technology',13,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(14,'Polymer Technology','polymer-technology',14,'Applied Science Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(15,'Automobile Electrics And Electronics','automobile-electrics-and-electronics',15,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(16,'Automotive Engineering','automotive-engineering',16,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(17,'Automotive Precision Machining','automotive-precision-machining',17,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(18,'Diesel Plant Fitting','diesel-plant-fitting',18,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(19,'Motor Cycle Machining','motor-cycle-machining',19,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(20,'Motor Vehicle Body Repairs','motor-vehicle-body-repairs',20,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(21,'Motor Vehicle Mechanics','motor-vehicle-mechanics',21,'Automotive Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(22,'Accountancy','accountancy',25,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(23,'Banking and Finance','banking-and-finance',26,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(24,'Health Services Management','health-services-management',27,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(25,'Human Resources Management','human-resources-management',28,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(26,'Pensions And Investments Management','pensions-and-investments-management',29,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(27,'Purchasing And Supply Management','purchasing-and-supply-management',30,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(28,'Sales And Marketing Management','sales-and-marketing-management',31,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(29,'Trainers Diploma In Education','trainers-diploma-in-education',33,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(30,'Transport And Logistics Management','transport-and-logistics-management',32,'Business And Management Studies','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(31,'Architectural Technology','architectural-technology',34,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(32,'Cartography And Geo-Visualization Theory Technology','cartography-and-geo-visualization-theory-technology',35,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(33,'Civil Engineering','civil-engineering',36,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(34,'Quantity Surveying','quantity-surveying',37,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(35,'Surveying and Geomatics','surveying-and-geomatics',38,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(36,'Urban And Regional Planning','urban-and-regional-planning',39,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(37,'Valuation And Estate Management','valuation-and-estate-management',40,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(38,'Water Resources And Irrigation Engineering','water-resources-and-irrigation-engineering',41,'Civil Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(39,'Building Technology','building-technology',42,'Construction Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(40,'Carpentry and Joinery','carpentry-and-joinery',43,'Construction Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(41,'Construction Engineering','construction-engineering',44,'Construction Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(42,'Painting and Decorating Technology','painting-and-decorating-technology',45,'Construction Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(43,'Plumbing and Drain Laying','plumbing-and-drain-laying',46,'Construction Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(44,'Computer Systems','computer-systems',47,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(45,'Domestic and Industrial Solar Installation','domestic-and-industrial-solar-installation',48,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(46,'Electrical Power Engineering','electrical-power-engineering',50,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(47,'Electronic Communication Systems','electronic-communication-systems',49,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(48,'Instrumentation and Control Systems','instrumentation-and-control-systems',51,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(49,'Microwave and Radar','microwave-and-radar',52,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(50,'Mobile and Satellite Communication','mobile-and-satellite-communication',53,'Electrical Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(51,'Information Technology','information-technology',22,'Information Communication Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(52,'Professional Computer Engineering','professional-computer-engineering',23,'Information Communication Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(53,'Professional Computing and Information Systems','professional-computing-and-information-systems',24,'Information Communication Technology','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(54,'Library and Information Sciences','library-and-information-sciences',54,'Library and Information Sciences','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(55,'Records Management and Information Sciences','records-management-and-information-sciences',55,'Library and Information Sciences','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(56,'Draughting and Design Technology','draughting-and-design-technology',56,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(57,'Fabrication Engineering','fabrication-engineering',57,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(58,'Machine Shop Engineering','machine-shop-engineering',58,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(59,'Mechanical Engineering','mechanical-engineering',59,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(60,'Millwright Works','millwright-works',60,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(61,'Plant Engineering','plant-engineering',61,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(62,'Production Engineering','production-engineering',62,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(63,'Refrigeration and Air Conditioning','refrigeration-and-air-conditioning',63,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(64,'Vehicle Body Building','vehicle-body-building',64,'Mechanical And Production Engineering','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(65,'Applied Art and Design','applied-art-and-design',65,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(66,'Design For Print','design-for-print',66,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(67,'Fine Arts','fine-arts',67,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(68,'Machine Printing','machine-printing',68,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(69,'Multimedia','multimedia',69,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(70,'Packaging Machine Minding','packaging-machine-minding',70,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(71,'Photography','photography',71,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(72,'Printing, Finishing and Converting','printing-finishing-and-converting',72,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(73,'Print Finishing Technology','print-finishing-technology',73,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(74,'Print Production Technology','print-production-technology',75,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(75,'Print Origination Technology','print-origination-technology',74,'Printing And Graphic Arts','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(76,'Broadcast Journalism','broadcast-journalism',76,'Mass Communication','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(77,'Mass Communication','mass-communication',77,'Mass Communication','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(78,'Print Journalism','print-journalism',78,'Mass Communication','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(79,'Public Relations','public-relations',79,'Mass Communication','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(80,'Office Management','office-management',80,'Office Management','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(81,'Bakery Technology and Management','bakery-technology-and-management',81,'Tourism And Hospitality','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(82,'Culinary Arts','culinary-arts',82,'Tourism And Hospitality','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(83,'Professional Cookery','professional-cookery',84,'Tourism And Hospitality','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(84,'Tourism and Hospitality Management','tourism-and-hospitality-management',83,'Tourism And Hospitality','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;

--
-- Table structure for table `department_application_steps`
--

DROP TABLE IF EXISTS `department_application_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_application_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `institution_department_id` bigint unsigned NOT NULL,
  `workflow_step_id` bigint unsigned NOT NULL,
  `position` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_application_steps_tenant_id_foreign` (`tenant_id`),
  KEY `department_application_steps_institution_department_id_foreign` (`institution_department_id`),
  KEY `department_application_steps_workflow_step_id_foreign` (`workflow_step_id`),
  CONSTRAINT `department_application_steps_institution_department_id_foreign` FOREIGN KEY (`institution_department_id`) REFERENCES `institution_departments` (`id`),
  CONSTRAINT `department_application_steps_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `department_application_steps_workflow_step_id_foreign` FOREIGN KEY (`workflow_step_id`) REFERENCES `workflow_steps` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_application_steps`
--

/*!40000 ALTER TABLE `department_application_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_application_steps` ENABLE KEYS */;

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
-- Table structure for table `department_workflow_steps`
--

DROP TABLE IF EXISTS `department_workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_workflow_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `steppable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `steppable_id` bigint unsigned NOT NULL,
  `role_ids` json DEFAULT NULL,
  `staff_ids` json DEFAULT NULL,
  `workflow_action_ids` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_workflow_steps_steppable_type_steppable_id_index` (`steppable_type`,`steppable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_workflow_steps`
--

/*!40000 ALTER TABLE `department_workflow_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_workflow_steps` ENABLE KEYS */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `is_academic` tinyint(1) DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Administration','administration',1,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Applied Arts','applied-arts',2,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Automotive Engineering','automotive-engineering',3,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Business And Management Studies','business-and-management-studies',4,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Civil Engineering','civil-engineering',5,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'Clinic','clinic',6,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'Construction Engineering','construction-engineering',7,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'Dean Of Students','dean-of-students',8,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'Electrical Engineering','electrical-engineering',9,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,'Executive','executive',10,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,'Finance','finance',11,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(12,'Human Resources','human-resources',12,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(13,'Information Communication Technology','information-communication-technology',13,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(14,'IT Unit','it-unit',14,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(15,'Library And Information Sciences','library-and-information-sciences',15,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(16,'Mass Communication','mass-communication',16,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(17,'Mechanical And Production Engineering','mechanical-and-production-engineering',17,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(18,'Office Management','office-management',18,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(19,'Printing And Graphics Arts','printing-and-graphics-arts',19,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(20,'Procurement Management Unit','procurement-management-unit',20,0,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(21,'Science Technology','science-technology',21,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(22,'Tourism And Hospitality','tourism-and-hospitality',22,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `districts` VALUES (1,'Bulawayo',1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Chitungwiza',2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Harare',2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Buhera',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Chimanimani',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'Chipinge',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'Makoni',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'Mutare',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'Mutasa',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,'Nyanga',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,'Bindura',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(12,'Guruve',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(13,'Mazowe',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(14,'Mbire',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(15,'Mount Darwin',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(16,'Muzarabani',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(17,'Rushinga',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(18,'Shamva',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(19,'Chikomba',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(20,'Goromonzi',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(21,'Marondera',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(22,'Mudzi',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(23,'Murehwa',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(24,'Mutoko',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(25,'Seke',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(26,'UMP (Uzumba-Maramba-Pfungwe)',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(27,'Wedza (Hwedza)',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(28,'Chegutu',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(29,'Hurungwe',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(30,'Kariba',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(31,'Makonde',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(32,'Mhondoro-Ngezi',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(33,'Sanyati',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(34,'Zvimba',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(35,'Bikita',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(36,'Chiredzi',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(37,'Chivi',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(38,'Gutu',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(39,'Masvingo',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(40,'Mwenezi',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(41,'Zaka',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(42,'Binga',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(43,'Bubi',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(44,'Hwange',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(45,'Lupane',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(46,'Nkayi',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(47,'Tsholotsho',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(48,'Umguza',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(49,'Beitbridge',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(50,'Bulilima',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(51,'Gwanda',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(52,'Insiza',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(53,'Mangwe',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(54,'Matobo',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(55,'Umzingwane',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(56,'Chirumhanzu',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(57,'Gokwe North',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(58,'Gokwe South',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(59,'Gweru',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(60,'Kwekwe',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(61,'Mberengwa',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(62,'Shurugwi',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(63,'Zvishavane',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `divisions` VALUES (1,'Business',1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Management',2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Pedagogics',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `employment_types` VALUES (1,'Full time','Full-time employment (35–40+ hours/week with benefits)','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Part time','Part-time employment (less than 35 hours/week)','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Temporary','Temporary or contract-based employment','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Freelance','Freelance or self-employed contractor work','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Intern','Internship or apprenticeship (temporary, for experience)','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'Casual','Casual work (on-call or irregular hours)','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'Seasonal','Seasonal employment (e.g. holiday or harvest periods)','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'Remote','Remote or telecommuting work (offsite)','2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `genders` VALUES (1,'Male',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Female',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
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
INSERT INTO `grades` VALUES (1,'A',1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'B',2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'C',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'D',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'E',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'U',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;

--
-- Table structure for table `id_types`
--

DROP TABLE IF EXISTS `id_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `id_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `id_types`
--

/*!40000 ALTER TABLE `id_types` DISABLE KEYS */;
INSERT INTO `id_types` VALUES (1,'Zimbabwean National ID',1,'A valid Zimbabwean National Identification Number issued by the Registrar General’s Office.','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Foreign Passport Number',0,'A valid passport number issued by a foreign government, subject to verification and approval.','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
/*!40000 ALTER TABLE `id_types` ENABLE KEYS */;

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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institution_departments`
--

/*!40000 ALTER TABLE `institution_departments` DISABLE KEYS */;
INSERT INTO `institution_departments` VALUES (1,1,1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,1,2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,1,3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,1,4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,1,5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,1,6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,1,7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,1,8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,1,9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,1,10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,1,11,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(12,1,12,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(13,1,13,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(14,1,14,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(15,1,15,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(16,1,16,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(17,1,17,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(18,1,18,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(19,1,19,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(20,1,20,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(21,1,21,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(22,1,22,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
INSERT INTO `languages` VALUES (1,'English',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `levels` VALUES (1,'ABMA Level 3',1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'ABMA Level 4',2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'ABMA Level 5',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'ABMA Level 6',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'NC',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'ND',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'HND',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'BTECH',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'SDP',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `marital_statuses` VALUES (1,'Divorced',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Engaged',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Married',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Single',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Widowed',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_create_tenants_table',1),(2,'0001_00_19_183342_create_races_table',1),(3,'0001_01_00_000000_create_statuses_table',1),(4,'0001_01_00_125713_create_genders_table',1),(5,'0001_01_00_183404_create_titles_table',1),(6,'0001_01_01_000000_create_users_table',1),(7,'0001_01_01_000001_create_cache_table',1),(8,'0001_01_01_000002_create_jobs_table',1),(9,'2024_11_06_174210_create_role_groups_table',1),(10,'2024_12_10_073103_create_media_table',1),(11,'2024_12_10_073229_create_activity_log_table',1),(12,'2024_12_10_073230_add_event_column_to_activity_log_table',1),(13,'2024_12_10_073231_add_batch_uuid_column_to_activity_log_table',1),(14,'2024_12_10_091905_create_modules_table',1),(15,'2024_12_10_112501_create_permission_tables',1),(16,'2025_01_18_202508_create_communication_methods_table',1),(17,'2025_01_18_222026_create_countries_table',1),(18,'2025_01_19_101127_create_payment_days_table',1),(19,'2025_01_19_101323_create_payment_frequencies_table',1),(20,'2025_01_19_101437_create_payment_methods_table',1),(21,'2025_01_19_140446_create_languages_table',1),(22,'2025_01_19_143527_create_provinces_table',1),(23,'2025_03_20_185152_create_addresses_table',1),(24,'2025_03_20_190050_create_contacts_table',1),(25,'2025_03_22_053137_create_address_types_table',1),(26,'2025_04_25_173642_create_departments_table',1),(27,'2025_04_25_173916_create_courses_table',1),(28,'2025_04_25_174007_create_divisions_table',1),(29,'2025_04_25_174046_create_grades_table',1),(30,'2025_04_25_174107_create_levels_table',1),(31,'2025_04_25_174151_create_relationships_table',1),(32,'2025_04_25_174216_create_subjects_table',1),(33,'2025_04_25_193714_create_mode_of_studies_table',1),(34,'2025_04_27_142505_create_districts_table',1),(35,'2025_04_28_135636_create_institution_departments_table',1),(36,'2025_05_06_231759_create_department_levels_table',1),(37,'2025_05_07_152341_create_personal_access_tokens_table',1),(38,'2025_05_09_073840_create_department_courses_table',1),(39,'2025_05_13_164228_create_department_course_levels_table',1),(40,'2025_05_22_063933_create_department_level_requirements_table',1),(41,'2025_05_26_082810_create_marital_statuses_table',1),(42,'2025_06_08_181246_create_id_types_table',1),(43,'2025_06_19_045841_create_students_table',1),(44,'2025_06_19_053738_create_student_programs_table',1),(45,'2025_06_20_012032_create_next_of_kin_table',1),(46,'2025_06_21_115803_create_religions_table',1),(47,'2025_06_23_054353_create_academic_levels_table',1),(48,'2025_06_23_125237_create_sponsors_table',1),(49,'2025_06_23_132119_create_sponsor_types_table',1),(50,'2025_06_26_034105_create_academic_records_table',1),(51,'2025_06_29_085659_create_workflow_steps_table',1),(52,'2025_06_30_125235_create_intake_periods_table',1),(53,'2025_07_00_195358_create_employment_types_table',1),(54,'2025_07_02_052540_create_staff_table',1),(55,'2025_07_03_135229_create_institution_department_staff_table',1),(56,'2025_07_06_001824_create_department_application_steps_table',1),(57,'2025_07_06_082931_create_workflow_step_actions_table',1),(58,'2025_07_06_151404_create_department_workflow_steps_table',1),(59,'2025_07_15_153559_create_notifications_table',1);
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
INSERT INTO `mode_of_studies` VALUES (1,'Full Time',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Part Time',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Block Release',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `model_has_roles` VALUES (2,'App\\Models\\Users\\User',1),(27,'App\\Models\\Users\\User',2);
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
INSERT INTO `modules` VALUES (1,'Accommodations','accommodations',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(2,'Acl','acl',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(3,'Communications','communications',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(4,'Dashboards','dashboards',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(5,'Enrolments','enrolments',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(6,'Examinations','examinations',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(7,'Institution','institution',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(8,'Other','other',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(9,'Reports','reports',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(10,'Root','root',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(11,'Settings','settings',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(12,'Shared','shared',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(13,'Students','students',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(14,'Tenants','tenants',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(15,'Users','users',NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL);
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
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;

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
INSERT INTO `payment_days` VALUES (1,'1',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,'2',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(3,'3',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(4,'4',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(5,'5',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(6,'6',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(7,'7',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(8,'8',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'9',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,'10',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,'11',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(12,'12',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(13,'13',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(14,'14',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(15,'15',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(16,'16',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(17,'17',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(18,'18',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(19,'19',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(20,'20',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(21,'21',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(22,'22',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(23,'23',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(24,'24',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(25,'25',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(26,'26',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(27,'27',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(28,'28',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(29,'29',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(30,'30',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(31,'31',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `payment_frequencies` VALUES (1,'Monthly',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,'Annually',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(3,'Once off',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL);
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
INSERT INTO `payment_methods` VALUES (1,'Credit Card',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,'Cash Payment',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(3,'Debit Order',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(4,'EFT',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(5,'Stop Order',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=194 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view:acl-settings',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(2,'viewAny:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(3,'view:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(4,'create:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(5,'update:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(6,'delete:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(7,'restore:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(8,'forceDelete:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(9,'import:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(10,'export:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(11,'viewAuditTrail:modules',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(12,'viewAny:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(13,'view:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(14,'create:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(15,'update:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(16,'delete:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(17,'restore:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(18,'forceDelete:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(19,'import:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(20,'export:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(21,'viewAuditTrail:roles',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(22,'viewAny:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(23,'view:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(24,'create:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(25,'update:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(26,'delete:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(27,'restore:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(28,'forceDelete:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(29,'import:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(30,'export:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(31,'viewAuditTrail:permissions',NULL,'web',2,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(32,'viewAny:communications',NULL,'web',3,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(33,'view:communications',NULL,'web',3,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(34,'create:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(35,'update:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(36,'delete:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(37,'restore:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(38,'forceDelete:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(39,'import:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(40,'export:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(41,'crud-settings:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(42,'viewAuditTrail:communications',NULL,'web',3,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(43,'viewAny:dashboards',NULL,'web',4,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(44,'view:dashboards',NULL,'web',4,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(45,'viewAny:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(46,'view:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(47,'create:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(48,'update:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(49,'delete:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(50,'restore:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(51,'forceDelete:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(52,'import:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(53,'export:reports',NULL,'web',9,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(54,'viewAny:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(55,'view:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(56,'create:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(57,'update:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(58,'delete:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(59,'restore:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(60,'forceDelete:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(61,'import:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(62,'export:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(63,'crud-settings:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(64,'viewAuditTrail:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(65,'manageOwnData:tenants',NULL,'web',14,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(66,'viewAny:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(67,'view:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(68,'create:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(69,'update:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(70,'delete:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(71,'restore:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(72,'forceDelete:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(73,'import:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(74,'export:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(75,'crud-settings:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(76,'viewAuditTrail:users',NULL,'web',15,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(77,'root:manage',NULL,'web',10,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(78,'view:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(79,'create:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(80,'update:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(81,'delete:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(82,'restore:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(83,'forceDelete:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(84,'import:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(85,'export:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(86,'viewAuditTrail:settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(87,'view:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(88,'create:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(89,'update:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(90,'delete:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(91,'restore:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(92,'forceDelete:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(93,'import:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(94,'export:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(95,'viewAuditTrail:institution-settings',NULL,'web',11,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(96,'viewAny:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(97,'view:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(98,'create:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(99,'update:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(100,'delete:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(101,'restore:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(102,'forceDelete:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(103,'import:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(104,'export:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(105,'viewAuditTrail:department-metadata',NULL,'web',7,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(106,'viewAny:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(107,'view:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(108,'create:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(109,'update:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(110,'delete:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(111,'restore:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(112,'forceDelete:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(113,'import:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(114,'export:bank-details',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(115,'viewAny:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(116,'view:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(117,'create:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(118,'update:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(119,'delete:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(120,'restore:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(121,'forceDelete:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(122,'import:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(123,'export:addresses',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(124,'viewAny:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(125,'view:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(126,'create:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(127,'update:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(128,'delete:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(129,'restore:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(130,'forceDelete:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(131,'import:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(132,'export:contacts',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(133,'viewAny:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(134,'view:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(135,'create:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(136,'update:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(137,'delete:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(138,'restore:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(139,'forceDelete:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(140,'import:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(141,'export:next-of-kins',NULL,'web',12,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(142,'viewOwnDashboard:students',NULL,'web',13,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(143,'manageOwnStudentPersonalDetails:students',NULL,'web',13,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(144,'manageOwnStudentProgramDetails:students',NULL,'web',13,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(145,'manageOwnStudentSponsorDetails:students',NULL,'web',13,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(146,'manageOwnStudentContactDetails:students',NULL,'web',13,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(147,'manageOwnStudentFinancialDetails:students',NULL,'web',13,'2025-07-16 06:37:11','2025-07-16 06:37:11',NULL),(148,'manageOwnStudentAcademicDetails:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(149,'manageStudentMetadata:admin',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(150,'viewAny:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(151,'view:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(152,'create:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(153,'update:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(154,'delete:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(155,'restore:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(156,'forceDelete:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(157,'import:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(158,'export:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(159,'crud-settings:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(160,'viewAuditTrail:students',NULL,'web',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(161,'viewAny:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(162,'view:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(163,'create:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(164,'update:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(165,'delete:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(166,'restore:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(167,'forceDelete:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(168,'import:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(169,'export:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(170,'crud-settings:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(171,'viewAuditTrail:enrolments',NULL,'web',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(172,'viewAny:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(173,'view:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(174,'create:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(175,'update:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(176,'delete:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(177,'restore:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(178,'forceDelete:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(179,'import:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(180,'export:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(181,'crud-settings:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(182,'viewAuditTrail:examinations',NULL,'web',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(183,'viewAny:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(184,'view:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(185,'create:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(186,'update:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(187,'delete:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(188,'restore:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(189,'forceDelete:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(190,'import:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(191,'export:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(192,'crud-settings:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(193,'viewAuditTrail:accommodations',NULL,'web',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
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
INSERT INTO `provinces` VALUES (1,'Bulawayo',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Harare',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Manicaland',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Mashonaland Central',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Mashonaland East',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'Mashonaland West',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'Masvingo',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'Matebeleland North',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'Matebeleland South',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,'Midlands',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,'Unknown Province',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `races` VALUES (1,'African',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Black',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(3,'White',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(4,'Colored',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(5,'Indian',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
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
INSERT INTO `relationships` VALUES (1,'Parent',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Spouse',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Guardian',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
INSERT INTO `religions` VALUES (1,'Christianity',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,'African Traditional Religion',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(3,'Islam',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(4,'Hinduism',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(5,'Buddhism',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(6,'Judaism',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(7,'Other Religions',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(8,'Religiously Unaffiliated',NULL,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL);
/*!40000 ALTER TABLE `religions` ENABLE KEYS */;

--
-- Table structure for table `role_groups`
--

DROP TABLE IF EXISTS `role_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_groups_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_groups`
--

/*!40000 ALTER TABLE `role_groups` DISABLE KEYS */;
INSERT INTO `role_groups` VALUES (1,'super-user','Super User','System-level user with access to all areas.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(2,'tesc','TESC','Tertiary Education Service Council (TESC) group.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(3,'executive','Executive','Executive leadership including principals, deans, registrars, and bursars.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(4,'academic','Academic','Teaching and research personnel such as lecturers heads of department.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(5,'administrative','Administrative','Administrative Staff (Non-Academic) involved in administration.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(6,'managerial','Managerial','Managerial Staff (Non-Academic) involved in management.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(7,'service-and-support','Service and support','Support and Service Staff (Non-Academic, Operational) providing technical, clerical, or facility-related support.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(8,'student','Student','Registered learners in the institution.','2025-07-16 06:37:10','2025-07-16 06:37:10',NULL);
/*!40000 ALTER TABLE `role_groups` ENABLE KEYS */;

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
INSERT INTO `role_has_permissions` VALUES (1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(52,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(66,2),(67,2),(68,2),(69,2),(70,2),(71,2),(72,2),(73,2),(74,2),(75,2),(76,2),(77,2),(78,2),(79,2),(80,2),(81,2),(82,2),(83,2),(84,2),(85,2),(86,2),(87,2),(88,2),(89,2),(90,2),(91,2),(92,2),(93,2),(94,2),(95,2),(96,2),(97,2),(98,2),(99,2),(100,2),(101,2),(102,2),(103,2),(104,2),(105,2),(106,2),(107,2),(108,2),(109,2),(110,2),(111,2),(112,2),(113,2),(114,2),(115,2),(116,2),(117,2),(118,2),(119,2),(120,2),(121,2),(122,2),(123,2),(124,2),(125,2),(126,2),(127,2),(128,2),(129,2),(130,2),(131,2),(132,2),(133,2),(138,2),(140,2),(141,2),(149,2),(150,2),(151,2),(152,2),(153,2),(154,2),(155,2),(156,2),(157,2),(158,2),(159,2),(160,2),(161,2),(162,2),(163,2),(164,2),(165,2),(166,2),(167,2),(168,2),(169,2),(170,2),(171,2),(172,2),(173,2),(174,2),(175,2),(176,2),(177,2),(178,2),(179,2),(180,2),(181,2),(182,2),(183,2),(184,2),(185,2),(186,2),(187,2),(188,2),(189,2),(190,2),(191,2),(192,2),(193,2),(134,27),(135,27),(136,27),(137,27),(139,27),(142,27),(143,27),(144,27),(145,27),(146,27),(147,27),(148,27);
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
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_group_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `roles_role_group_id_foreign` (`role_group_id`),
  CONSTRAINT `roles_role_group_id_foreign` FOREIGN KEY (`role_group_id`) REFERENCES `role_groups` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super User','super-user',1,'Power user with elevated privileges for system oversight.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Super Administrator','super-administrator',1,'Has unrestricted access to all system functions.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(3,'TESC','tesc',2,'Tertiary Education Service Council (TESC) group responsible for overseeing tertiary education policies and standards.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(4,'Principal','principal',3,'The head of the institution.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(5,'Vice Principal','vice-principal',3,'Deputy to the Principal.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(6,'Registrar','registrar',3,'Oversees academic records and administrative operations.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(7,'Dean','dean',3,'Leads a faculty or academic division.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(8,'Bursar','bursar',3,'Oversees and Manages finances of the institution.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(9,'Librarian','librarian',3,'Manages library resources and services.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(10,'Lecturer','lecturer',4,'Delivers academic content to students.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(11,'Lecturer in Charge','lecturer-in-charge',4,'Coordinates lecturers within a module.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(12,'Senior Lecturer','senior-lecturer',4,'Senior academic with additional responsibilities.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(13,'Head of Division','head-of-division',4,'Leads a division and oversees departments within it.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(14,'Head of Department','head-of-department',4,'Responsible for a specific academic department.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(15,'Selection Officer','selection-officer',4,'Manages student selection processes.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(16,'IT Manager','it-manager',6,'Oversees IT infrastructure and strategy.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(17,'Accountant','accountant',6,'Manages finances of the institution.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(18,'HR Officer','hr-officer',6,'Handles staff recruitment and welfare.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(19,'Administrative Officer','administrative-officer',6,'Handles applications and enrollment.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(20,'Accountant Assistant','accountant-assistant',5,'Provides support to Accountant.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(21,'HR Officer Assistant','hr-officer-assistant',5,'Helps the HR Officer.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(22,'Administrative Assistant','administrative-assistant',5,'Provides administrative support.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(23,'IT Systems Administrator','it-systems-administrator',5,'Provides IT systems administration.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(24,'IT Support Technician','it-support-technician',7,'Provides technical support.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(25,'Lab Technician','lab-technician',7,'Prepares and maintains lab equipment.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(26,'Security Officer','security-officer',7,'Maintains safety and security.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(27,'Student','student',8,'Learner enrolled in the institution.','web','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
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
INSERT INTO `sessions` VALUES ('jIbqG5TJhwIZQ9Opq8wlyOrtB0Cmxub4fq4pNROx',2,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiSHF2Y0JWeTg1VHJudmtIY0d5T0paYnFkYnlTcjNpcGwzWlZ4Y1EySiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjYxOiJodHRwczovL2hyZXBvbHkudGVzdC9hcGkvdjEvaW5zdGl0dXRpb24tZGVwYXJ0bWVudHMvMTMvbGV2ZWxzIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJG5sLzk0WXh4OEI5dndMdVZQN1BXc3VwSG5OZ0NrOEdnS01XRHpiTnhUcktTMWswMy9pc1R5Ijt9',1752686517);
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
INSERT INTO `sponsor_types` VALUES (1,'Person',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Company',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Church',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Other Organization',NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
  `title_id` bigint unsigned NOT NULL,
  `gender_id` bigint unsigned NOT NULL,
  `marital_status_id` bigint unsigned NOT NULL,
  `race_id` bigint unsigned DEFAULT NULL,
  `id_type_id` bigint unsigned DEFAULT NULL,
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
  UNIQUE KEY `staff_id_number_unique` (`id_number`),
  UNIQUE KEY `staff_passport_number_unique` (`passport_number`),
  KEY `staff_tenant_id_foreign` (`tenant_id`),
  KEY `staff_user_id_foreign` (`user_id`),
  KEY `staff_employment_type_id_foreign` (`employment_type_id`),
  KEY `staff_title_id_foreign` (`title_id`),
  KEY `staff_gender_id_foreign` (`gender_id`),
  KEY `staff_marital_status_id_foreign` (`marital_status_id`),
  KEY `staff_id_type_id_foreign` (`id_type_id`),
  CONSTRAINT `staff_employment_type_id_foreign` FOREIGN KEY (`employment_type_id`) REFERENCES `employment_types` (`id`),
  CONSTRAINT `staff_gender_id_foreign` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`),
  CONSTRAINT `staff_id_type_id_foreign` FOREIGN KEY (`id_type_id`) REFERENCES `id_types` (`id`),
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
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
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
INSERT INTO `statuses` VALUES (1,'Active',1,'Currently active and in use','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Waiting Approval',0,'Pending approval from an authority','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(3,'Inactive',0,'Not currently active','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
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
  `department_application_step_id` bigint unsigned DEFAULT NULL,
  `program_status_id` bigint unsigned DEFAULT NULL,
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
  `id_type_id` bigint unsigned NOT NULL,
  `student_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  UNIQUE KEY `students_student_number_unique` (`student_number`),
  UNIQUE KEY `students_id_number_unique` (`id_number`),
  UNIQUE KEY `students_passport_number_unique` (`passport_number`),
  KEY `students_tenant_id_foreign` (`tenant_id`),
  KEY `students_title_id_foreign` (`title_id`),
  KEY `students_gender_id_foreign` (`gender_id`),
  KEY `students_marital_status_id_foreign` (`marital_status_id`),
  KEY `students_id_type_id_foreign` (`id_type_id`),
  CONSTRAINT `students_gender_id_foreign` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`),
  CONSTRAINT `students_id_type_id_foreign` FOREIGN KEY (`id_type_id`) REFERENCES `id_types` (`id`),
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
INSERT INTO `subjects` VALUES (1,'Accounts',1,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(2,'Agriculture',2,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(3,'Art',3,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(4,'Bible Knowledge',4,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(5,'Building Studies',5,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(6,'Business and Enterprise Skills',6,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(7,'Business Studies',7,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(8,'Chinese',8,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(9,'Commerce',9,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(10,'Computer Science',10,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(11,'Design and Technology',11,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(12,'Economics',12,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(13,'English',13,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(14,'Fashion and Fabrics',14,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(15,'Food and Nutrition',15,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(16,'French',16,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(17,'Geography',17,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(18,'German',18,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(19,'History',19,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(20,'Integrated Science',20,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(21,'Literature in English',21,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(22,'Mathematics',22,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(23,'Metal Technology and Design',23,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(24,'Music',24,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(25,'Ndebele',25,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(26,'Physical Education, Sport and Mass Displays',26,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(27,'Religious Studies',27,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(28,'Shona',28,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(29,'Spanish',29,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(30,'Technical Graphics',30,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL),(31,'Wood Technology and Design',31,NULL,'2025-07-16 06:37:14','2025-07-16 06:37:14',NULL);
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
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
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
INSERT INTO `tenants` VALUES (1,'Harare Poly',1,NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL),(2,'Penstej Systems',0,NULL,'2025-07-16 06:37:10','2025-07-16 06:37:10',NULL);
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
INSERT INTO `titles` VALUES (1,'Mr',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Mrs',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(3,'Miss',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(4,'Dr',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(5,'Prof',NULL,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,NULL,'Super','','Administrator','penstejdevelopers@gmail.com','0788104809','2025-07-16 06:37:13','$2y$12$rsDGlzAJ20PmgqJtu.leXuyBlagYpP.4/.8dKwYBmp2Nny3rpdR1m',NULL,0,NULL,1,'2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(2,1,NULL,'James','Jimmy','Gudhlanga','jimmyneds@gmail.com',NULL,'2025-07-16 06:40:39','$2y$12$nl/94Yxx8B9vwLuVP7PWsupHnNgCk8GgKMWDzbNxTrKS1k03/isTy','2025-07-16 11:21:15',2,'gESsVZVpuvruPvGEgsiyrv2gCrHLA0V6Cpn4VGv889sJ63TnCRttKEssn9P4',1,'2025-07-16 06:40:09','2025-07-16 11:21:15',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;

--
-- Table structure for table `workflow_step_actions`
--

DROP TABLE IF EXISTS `workflow_step_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_step_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_step_actions_title_unique` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_step_actions`
--

/*!40000 ALTER TABLE `workflow_step_actions` DISABLE KEYS */;
INSERT INTO `workflow_step_actions` VALUES (1,'send-email-to-applicant','Send Email To Applicant','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'send-email-to-staff','Send Email To Staff','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(3,'create-payment-link','Create Payment Link','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(4,'request-documents','Request Documents','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(5,'verify-identity','Verify Identity','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(6,'mark-step-complete','Mark Step Complete','2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(7,'revert-step','Revert Step','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(8,'upload-receipt','Upload Receipt','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(9,'add-internal-note','Add Internal Note','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL),(10,'assign-staff','Assign Staff','2025-07-16 06:37:13','2025-07-16 06:37:13',NULL);
/*!40000 ALTER TABLE `workflow_step_actions` ENABLE KEYS */;

--
-- Table structure for table `workflow_steps`
--

DROP TABLE IF EXISTS `workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `position` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_steps_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_steps`
--

/*!40000 ALTER TABLE `workflow_steps` DISABLE KEYS */;
INSERT INTO `workflow_steps` VALUES (1,'Submitted','Application has been submitted and is awaiting review.',1,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(2,'Awaiting Application Fee Payment','Pending payment of application or registration fees.',2,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(3,'In Review','Application is currently under review by staff.',3,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(4,'Awaiting Requirements','Additional documents or info required.',4,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(5,'Interview Scheduled','Interview has been scheduled with the applicant.',5,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(6,'Interview Completed','Interview has been completed and is under consideration.',6,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(7,'Decision Pending','A final admission decision is being made.',7,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(8,'Accepted / Offer Made','Offer has been made to the applicant.',8,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(9,'Waitlisted','Applicant has been waitlisted.',9,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(10,'Rejected','Application has been rejected.',10,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(11,'Offer Accepted','Offer has been accepted by the applicant.',11,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(12,'Offer Declined','Applicant declined the offer.',12,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(13,'Awaiting fees payment','Awaiting fees payment.',13,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL),(14,'Enrolled / Registered','Applicant has enrolled and completed registration.',14,'2025-07-16 06:37:12','2025-07-16 06:37:12',NULL);
/*!40000 ALTER TABLE `workflow_steps` ENABLE KEYS */;

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

-- Dump completed on 2025-07-16 19:45:16
