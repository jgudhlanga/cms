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
INSERT INTO `academic_levels` VALUES (1,'Primary school',NULL,1,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Secondary school',NULL,2,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Advanced Level',NULL,3,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Tertiary Level',NULL,4,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=760 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'Tenant','created','App\\Models\\Tenants\\Tenant','created',1,NULL,NULL,'{\"attributes\": {\"meta\": null, \"name\": \"Harare Poly\", \"is_active\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(2,'Tenant','created','App\\Models\\Tenants\\Tenant','created',2,NULL,NULL,'{\"attributes\": {\"meta\": null, \"name\": \"Penstej Systems\", \"is_active\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(3,'AclModule','created','App\\Models\\Acl\\Module','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"accommodations\", \"title\": \"Accommodations\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(4,'AclModule','created','App\\Models\\Acl\\Module','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"acl\", \"title\": \"Acl\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(5,'AclModule','created','App\\Models\\Acl\\Module','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"communications\", \"title\": \"Communications\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(6,'AclModule','created','App\\Models\\Acl\\Module','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"dashboards\", \"title\": \"Dashboards\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(7,'AclModule','created','App\\Models\\Acl\\Module','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"enrolments\", \"title\": \"Enrolments\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(8,'AclModule','created','App\\Models\\Acl\\Module','created',6,NULL,NULL,'{\"attributes\": {\"slug\": \"examinations\", \"title\": \"Examinations\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(9,'AclModule','created','App\\Models\\Acl\\Module','created',7,NULL,NULL,'{\"attributes\": {\"slug\": \"institution\", \"title\": \"Institution\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(10,'AclModule','created','App\\Models\\Acl\\Module','created',8,NULL,NULL,'{\"attributes\": {\"slug\": \"other\", \"title\": \"Other\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(11,'AclModule','created','App\\Models\\Acl\\Module','created',9,NULL,NULL,'{\"attributes\": {\"slug\": \"reports\", \"title\": \"Reports\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(12,'AclModule','created','App\\Models\\Acl\\Module','created',10,NULL,NULL,'{\"attributes\": {\"slug\": \"root\", \"title\": \"Root\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(13,'AclModule','created','App\\Models\\Acl\\Module','created',11,NULL,NULL,'{\"attributes\": {\"slug\": \"settings\", \"title\": \"Settings\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(14,'AclModule','created','App\\Models\\Acl\\Module','created',12,NULL,NULL,'{\"attributes\": {\"slug\": \"shared\", \"title\": \"Shared\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(15,'AclModule','created','App\\Models\\Acl\\Module','created',13,NULL,NULL,'{\"attributes\": {\"slug\": \"students\", \"title\": \"Students\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(16,'AclModule','created','App\\Models\\Acl\\Module','created',14,NULL,NULL,'{\"attributes\": {\"slug\": \"tenants\", \"title\": \"Tenants\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(17,'AclModule','created','App\\Models\\Acl\\Module','created',15,NULL,NULL,'{\"attributes\": {\"slug\": \"users\", \"title\": \"Users\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(18,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super User\", \"slug\": \"super-user\", \"description\": \"System-level user with access to all areas.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(19,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"TESC\", \"slug\": \"tesc\", \"description\": \"Tertiary Education Service Council (TESC) group.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(20,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Executive\", \"slug\": \"executive\", \"description\": \"Executive leadership including deans, directors, or heads of departments.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(21,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Academic Staff\", \"slug\": \"academic-staff\", \"description\": \"Teaching and research personnel such as lecturers and professors.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(22,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Administrative & Managerial Staff\", \"slug\": \"administrative-managerial-staff\", \"description\": \"Administrative Staff (Non-Academic, Managerial) involved in management or administration.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(23,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Support Service Staff\", \"slug\": \"support-service-staff\", \"description\": \"Support and Service Staff (Non-Academic, Operational) providing technical, clerical, or facility-related support.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(24,'RoleGroup','created','App\\Models\\Acl\\RoleGroup','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Student\", \"slug\": \"student\", \"description\": \"Registered learners in the institution.\"}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(25,'Permission','created','App\\Models\\Acl\\Permission','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"view:acl-settings\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(26,'Permission','created','App\\Models\\Acl\\Permission','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(27,'Permission','created','App\\Models\\Acl\\Permission','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"view:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(28,'Permission','created','App\\Models\\Acl\\Permission','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"create:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(29,'Permission','created','App\\Models\\Acl\\Permission','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"update:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(30,'Permission','created','App\\Models\\Acl\\Permission','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"delete:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(31,'Permission','created','App\\Models\\Acl\\Permission','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"restore:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(32,'Permission','created','App\\Models\\Acl\\Permission','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(33,'Permission','created','App\\Models\\Acl\\Permission','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"import:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(34,'Permission','created','App\\Models\\Acl\\Permission','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"export:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(35,'Permission','created','App\\Models\\Acl\\Permission','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:modules\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(36,'Permission','created','App\\Models\\Acl\\Permission','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(37,'Permission','created','App\\Models\\Acl\\Permission','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"view:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(38,'Permission','created','App\\Models\\Acl\\Permission','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"create:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(39,'Permission','created','App\\Models\\Acl\\Permission','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"update:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(40,'Permission','created','App\\Models\\Acl\\Permission','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"delete:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(41,'Permission','created','App\\Models\\Acl\\Permission','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"restore:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(42,'Permission','created','App\\Models\\Acl\\Permission','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(43,'Permission','created','App\\Models\\Acl\\Permission','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"import:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(44,'Permission','created','App\\Models\\Acl\\Permission','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"export:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(45,'Permission','created','App\\Models\\Acl\\Permission','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:roles\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(46,'Permission','created','App\\Models\\Acl\\Permission','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(47,'Permission','created','App\\Models\\Acl\\Permission','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"view:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(48,'Permission','created','App\\Models\\Acl\\Permission','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"create:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(49,'Permission','created','App\\Models\\Acl\\Permission','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"update:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(50,'Permission','created','App\\Models\\Acl\\Permission','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"delete:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(51,'Permission','created','App\\Models\\Acl\\Permission','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"restore:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(52,'Permission','created','App\\Models\\Acl\\Permission','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(53,'Permission','created','App\\Models\\Acl\\Permission','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"import:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(54,'Permission','created','App\\Models\\Acl\\Permission','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"export:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(55,'Permission','created','App\\Models\\Acl\\Permission','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:permissions\", \"module_id\": 2, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(56,'Permission','created','App\\Models\\Acl\\Permission','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(57,'Permission','created','App\\Models\\Acl\\Permission','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"view:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(58,'Permission','created','App\\Models\\Acl\\Permission','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"create:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(59,'Permission','created','App\\Models\\Acl\\Permission','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"update:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(60,'Permission','created','App\\Models\\Acl\\Permission','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"delete:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(61,'Permission','created','App\\Models\\Acl\\Permission','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"restore:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(62,'Permission','created','App\\Models\\Acl\\Permission','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(63,'Permission','created','App\\Models\\Acl\\Permission','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"import:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08'),(64,'Permission','created','App\\Models\\Acl\\Permission','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"export:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(65,'Permission','created','App\\Models\\Acl\\Permission','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(66,'Permission','created','App\\Models\\Acl\\Permission','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:communications\", \"module_id\": 3, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(67,'Permission','created','App\\Models\\Acl\\Permission','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:dashboards\", \"module_id\": 4, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(68,'Permission','created','App\\Models\\Acl\\Permission','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"view:dashboards\", \"module_id\": 4, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(69,'Permission','created','App\\Models\\Acl\\Permission','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(70,'Permission','created','App\\Models\\Acl\\Permission','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"view:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(71,'Permission','created','App\\Models\\Acl\\Permission','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"create:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(72,'Permission','created','App\\Models\\Acl\\Permission','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"update:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(73,'Permission','created','App\\Models\\Acl\\Permission','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"delete:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(74,'Permission','created','App\\Models\\Acl\\Permission','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"restore:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(75,'Permission','created','App\\Models\\Acl\\Permission','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(76,'Permission','created','App\\Models\\Acl\\Permission','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"import:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(77,'Permission','created','App\\Models\\Acl\\Permission','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"export:reports\", \"module_id\": 9, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(78,'Permission','created','App\\Models\\Acl\\Permission','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(79,'Permission','created','App\\Models\\Acl\\Permission','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"view:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(80,'Permission','created','App\\Models\\Acl\\Permission','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"create:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(81,'Permission','created','App\\Models\\Acl\\Permission','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"update:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(82,'Permission','created','App\\Models\\Acl\\Permission','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"delete:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(83,'Permission','created','App\\Models\\Acl\\Permission','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"restore:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(84,'Permission','created','App\\Models\\Acl\\Permission','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(85,'Permission','created','App\\Models\\Acl\\Permission','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"import:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(86,'Permission','created','App\\Models\\Acl\\Permission','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"export:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(87,'Permission','created','App\\Models\\Acl\\Permission','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(88,'Permission','created','App\\Models\\Acl\\Permission','created',64,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(89,'Permission','created','App\\Models\\Acl\\Permission','created',65,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnData:tenants\", \"module_id\": 14, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(90,'Permission','created','App\\Models\\Acl\\Permission','created',66,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(91,'Permission','created','App\\Models\\Acl\\Permission','created',67,NULL,NULL,'{\"attributes\": {\"name\": \"view:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(92,'Permission','created','App\\Models\\Acl\\Permission','created',68,NULL,NULL,'{\"attributes\": {\"name\": \"create:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(93,'Permission','created','App\\Models\\Acl\\Permission','created',69,NULL,NULL,'{\"attributes\": {\"name\": \"update:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(94,'Permission','created','App\\Models\\Acl\\Permission','created',70,NULL,NULL,'{\"attributes\": {\"name\": \"delete:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(95,'Permission','created','App\\Models\\Acl\\Permission','created',71,NULL,NULL,'{\"attributes\": {\"name\": \"restore:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(96,'Permission','created','App\\Models\\Acl\\Permission','created',72,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(97,'Permission','created','App\\Models\\Acl\\Permission','created',73,NULL,NULL,'{\"attributes\": {\"name\": \"import:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(98,'Permission','created','App\\Models\\Acl\\Permission','created',74,NULL,NULL,'{\"attributes\": {\"name\": \"export:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(99,'Permission','created','App\\Models\\Acl\\Permission','created',75,NULL,NULL,'{\"attributes\": {\"name\": \"crud-settings:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(100,'Permission','created','App\\Models\\Acl\\Permission','created',76,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:users\", \"module_id\": 15, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(101,'Permission','created','App\\Models\\Acl\\Permission','created',77,NULL,NULL,'{\"attributes\": {\"name\": \"root:manage\", \"module_id\": 10, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(102,'Permission','created','App\\Models\\Acl\\Permission','created',78,NULL,NULL,'{\"attributes\": {\"name\": \"view:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(103,'Permission','created','App\\Models\\Acl\\Permission','created',79,NULL,NULL,'{\"attributes\": {\"name\": \"create:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(104,'Permission','created','App\\Models\\Acl\\Permission','created',80,NULL,NULL,'{\"attributes\": {\"name\": \"update:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(105,'Permission','created','App\\Models\\Acl\\Permission','created',81,NULL,NULL,'{\"attributes\": {\"name\": \"delete:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(106,'Permission','created','App\\Models\\Acl\\Permission','created',82,NULL,NULL,'{\"attributes\": {\"name\": \"restore:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(107,'Permission','created','App\\Models\\Acl\\Permission','created',83,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(108,'Permission','created','App\\Models\\Acl\\Permission','created',84,NULL,NULL,'{\"attributes\": {\"name\": \"import:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(109,'Permission','created','App\\Models\\Acl\\Permission','created',85,NULL,NULL,'{\"attributes\": {\"name\": \"export:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(110,'Permission','created','App\\Models\\Acl\\Permission','created',86,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(111,'Permission','created','App\\Models\\Acl\\Permission','created',87,NULL,NULL,'{\"attributes\": {\"name\": \"view:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(112,'Permission','created','App\\Models\\Acl\\Permission','created',88,NULL,NULL,'{\"attributes\": {\"name\": \"create:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(113,'Permission','created','App\\Models\\Acl\\Permission','created',89,NULL,NULL,'{\"attributes\": {\"name\": \"update:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(114,'Permission','created','App\\Models\\Acl\\Permission','created',90,NULL,NULL,'{\"attributes\": {\"name\": \"delete:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(115,'Permission','created','App\\Models\\Acl\\Permission','created',91,NULL,NULL,'{\"attributes\": {\"name\": \"restore:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(116,'Permission','created','App\\Models\\Acl\\Permission','created',92,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(117,'Permission','created','App\\Models\\Acl\\Permission','created',93,NULL,NULL,'{\"attributes\": {\"name\": \"import:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(118,'Permission','created','App\\Models\\Acl\\Permission','created',94,NULL,NULL,'{\"attributes\": {\"name\": \"export:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(119,'Permission','created','App\\Models\\Acl\\Permission','created',95,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:institution-settings\", \"module_id\": 11, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(120,'Permission','created','App\\Models\\Acl\\Permission','created',96,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(121,'Permission','created','App\\Models\\Acl\\Permission','created',97,NULL,NULL,'{\"attributes\": {\"name\": \"view:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(122,'Permission','created','App\\Models\\Acl\\Permission','created',98,NULL,NULL,'{\"attributes\": {\"name\": \"create:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(123,'Permission','created','App\\Models\\Acl\\Permission','created',99,NULL,NULL,'{\"attributes\": {\"name\": \"update:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(124,'Permission','created','App\\Models\\Acl\\Permission','created',100,NULL,NULL,'{\"attributes\": {\"name\": \"delete:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(125,'Permission','created','App\\Models\\Acl\\Permission','created',101,NULL,NULL,'{\"attributes\": {\"name\": \"restore:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(126,'Permission','created','App\\Models\\Acl\\Permission','created',102,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(127,'Permission','created','App\\Models\\Acl\\Permission','created',103,NULL,NULL,'{\"attributes\": {\"name\": \"import:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(128,'Permission','created','App\\Models\\Acl\\Permission','created',104,NULL,NULL,'{\"attributes\": {\"name\": \"export:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(129,'Permission','created','App\\Models\\Acl\\Permission','created',105,NULL,NULL,'{\"attributes\": {\"name\": \"viewAuditTrail:department-metadata\", \"module_id\": 7, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(130,'Permission','created','App\\Models\\Acl\\Permission','created',106,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(131,'Permission','created','App\\Models\\Acl\\Permission','created',107,NULL,NULL,'{\"attributes\": {\"name\": \"view:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(132,'Permission','created','App\\Models\\Acl\\Permission','created',108,NULL,NULL,'{\"attributes\": {\"name\": \"create:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(133,'Permission','created','App\\Models\\Acl\\Permission','created',109,NULL,NULL,'{\"attributes\": {\"name\": \"update:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(134,'Permission','created','App\\Models\\Acl\\Permission','created',110,NULL,NULL,'{\"attributes\": {\"name\": \"delete:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(135,'Permission','created','App\\Models\\Acl\\Permission','created',111,NULL,NULL,'{\"attributes\": {\"name\": \"restore:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(136,'Permission','created','App\\Models\\Acl\\Permission','created',112,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(137,'Permission','created','App\\Models\\Acl\\Permission','created',113,NULL,NULL,'{\"attributes\": {\"name\": \"import:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(138,'Permission','created','App\\Models\\Acl\\Permission','created',114,NULL,NULL,'{\"attributes\": {\"name\": \"export:bank-details\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(139,'Permission','created','App\\Models\\Acl\\Permission','created',115,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(140,'Permission','created','App\\Models\\Acl\\Permission','created',116,NULL,NULL,'{\"attributes\": {\"name\": \"view:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(141,'Permission','created','App\\Models\\Acl\\Permission','created',117,NULL,NULL,'{\"attributes\": {\"name\": \"create:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(142,'Permission','created','App\\Models\\Acl\\Permission','created',118,NULL,NULL,'{\"attributes\": {\"name\": \"update:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(143,'Permission','created','App\\Models\\Acl\\Permission','created',119,NULL,NULL,'{\"attributes\": {\"name\": \"delete:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(144,'Permission','created','App\\Models\\Acl\\Permission','created',120,NULL,NULL,'{\"attributes\": {\"name\": \"restore:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(145,'Permission','created','App\\Models\\Acl\\Permission','created',121,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(146,'Permission','created','App\\Models\\Acl\\Permission','created',122,NULL,NULL,'{\"attributes\": {\"name\": \"import:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(147,'Permission','created','App\\Models\\Acl\\Permission','created',123,NULL,NULL,'{\"attributes\": {\"name\": \"export:addresses\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(148,'Permission','created','App\\Models\\Acl\\Permission','created',124,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(149,'Permission','created','App\\Models\\Acl\\Permission','created',125,NULL,NULL,'{\"attributes\": {\"name\": \"view:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(150,'Permission','created','App\\Models\\Acl\\Permission','created',126,NULL,NULL,'{\"attributes\": {\"name\": \"create:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(151,'Permission','created','App\\Models\\Acl\\Permission','created',127,NULL,NULL,'{\"attributes\": {\"name\": \"update:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(152,'Permission','created','App\\Models\\Acl\\Permission','created',128,NULL,NULL,'{\"attributes\": {\"name\": \"delete:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(153,'Permission','created','App\\Models\\Acl\\Permission','created',129,NULL,NULL,'{\"attributes\": {\"name\": \"restore:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(154,'Permission','created','App\\Models\\Acl\\Permission','created',130,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(155,'Permission','created','App\\Models\\Acl\\Permission','created',131,NULL,NULL,'{\"attributes\": {\"name\": \"import:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(156,'Permission','created','App\\Models\\Acl\\Permission','created',132,NULL,NULL,'{\"attributes\": {\"name\": \"export:contacts\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(157,'Permission','created','App\\Models\\Acl\\Permission','created',133,NULL,NULL,'{\"attributes\": {\"name\": \"viewAny:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(158,'Permission','created','App\\Models\\Acl\\Permission','created',134,NULL,NULL,'{\"attributes\": {\"name\": \"view:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(159,'Permission','created','App\\Models\\Acl\\Permission','created',135,NULL,NULL,'{\"attributes\": {\"name\": \"create:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(160,'Permission','created','App\\Models\\Acl\\Permission','created',136,NULL,NULL,'{\"attributes\": {\"name\": \"update:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(161,'Permission','created','App\\Models\\Acl\\Permission','created',137,NULL,NULL,'{\"attributes\": {\"name\": \"delete:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(162,'Permission','created','App\\Models\\Acl\\Permission','created',138,NULL,NULL,'{\"attributes\": {\"name\": \"restore:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(163,'Permission','created','App\\Models\\Acl\\Permission','created',139,NULL,NULL,'{\"attributes\": {\"name\": \"forceDelete:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(164,'Permission','created','App\\Models\\Acl\\Permission','created',140,NULL,NULL,'{\"attributes\": {\"name\": \"import:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(165,'Permission','created','App\\Models\\Acl\\Permission','created',141,NULL,NULL,'{\"attributes\": {\"name\": \"export:next-of-kins\", \"module_id\": 12, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(166,'Permission','created','App\\Models\\Acl\\Permission','created',142,NULL,NULL,'{\"attributes\": {\"name\": \"viewOwnDashboard:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(167,'Permission','created','App\\Models\\Acl\\Permission','created',143,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentPersonalDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:09','2025-07-07 04:22:09'),(168,'Permission','created','App\\Models\\Acl\\Permission','created',144,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentProgramDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(169,'Permission','created','App\\Models\\Acl\\Permission','created',145,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentSponsorDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(170,'Permission','created','App\\Models\\Acl\\Permission','created',146,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentContactDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(171,'Permission','created','App\\Models\\Acl\\Permission','created',147,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentFinancialDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(172,'Permission','created','App\\Models\\Acl\\Permission','created',148,NULL,NULL,'{\"attributes\": {\"name\": \"manageOwnStudentAcademicDetails:students\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(173,'Permission','created','App\\Models\\Acl\\Permission','created',149,NULL,NULL,'{\"attributes\": {\"name\": \"manageStudentMetadata:admin\", \"module_id\": 13, \"guard_name\": \"web\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(174,'Role','created','App\\Models\\Acl\\Role','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super User\", \"slug\": \"super-user\", \"guard_name\": \"web\", \"description\": \"Power user with elevated privileges for system oversight.\", \"role_group_id\": 1}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(175,'Role','created','App\\Models\\Acl\\Role','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Super Administrator\", \"slug\": \"super-administrator\", \"guard_name\": \"web\", \"description\": \"Has unrestricted access to all system functions.\", \"role_group_id\": 1}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(176,'Role','created','App\\Models\\Acl\\Role','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"TESC\", \"slug\": \"tesc\", \"guard_name\": \"web\", \"description\": \"Tertiary Education Service Council (TESC) group responsible for overseeing tertiary education policies and standards.\", \"role_group_id\": 2}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(177,'Role','created','App\\Models\\Acl\\Role','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Principal\", \"slug\": \"principal\", \"guard_name\": \"web\", \"description\": \"The head of the institution.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(178,'Role','created','App\\Models\\Acl\\Role','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Vice Principal\", \"slug\": \"vice-principal\", \"guard_name\": \"web\", \"description\": \"Deputy to the Principal.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(179,'Role','created','App\\Models\\Acl\\Role','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Registrar\", \"slug\": \"registrar\", \"guard_name\": \"web\", \"description\": \"Oversees academic records and administrative operations.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(180,'Role','created','App\\Models\\Acl\\Role','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Selection Officer\", \"slug\": \"selection-officer\", \"guard_name\": \"web\", \"description\": \"Manages student selection processes.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(181,'Role','created','App\\Models\\Acl\\Role','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Dean\", \"slug\": \"dean\", \"guard_name\": \"web\", \"description\": \"Leads a faculty or academic division.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(182,'Role','created','App\\Models\\Acl\\Role','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Head of Division\", \"slug\": \"head-of-division\", \"guard_name\": \"web\", \"description\": \"Leads a division and oversees departments within it.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(183,'Role','created','App\\Models\\Acl\\Role','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Head of Department\", \"slug\": \"head-of-department\", \"guard_name\": \"web\", \"description\": \"Responsible for a specific academic department.\", \"role_group_id\": 3}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(184,'Role','created','App\\Models\\Acl\\Role','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Lecturer\", \"slug\": \"lecturer\", \"guard_name\": \"web\", \"description\": \"Delivers academic content to students.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(185,'Role','created','App\\Models\\Acl\\Role','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Lecturer in Charge\", \"slug\": \"lecturer-in-charge\", \"guard_name\": \"web\", \"description\": \"Coordinates lecturers within a module.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(186,'Role','created','App\\Models\\Acl\\Role','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Senior Lecturer\", \"slug\": \"senior-lecturer\", \"guard_name\": \"web\", \"description\": \"Senior academic with additional responsibilities.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(187,'Role','created','App\\Models\\Acl\\Role','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Tutor\", \"slug\": \"tutor\", \"guard_name\": \"web\", \"description\": \"Supports students in tutorials and small groups.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(188,'Role','created','App\\Models\\Acl\\Role','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Researcher\", \"slug\": \"researcher\", \"guard_name\": \"web\", \"description\": \"Conducts academic or scientific research.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(189,'Role','created','App\\Models\\Acl\\Role','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Professor\", \"slug\": \"professor\", \"guard_name\": \"web\", \"description\": \"Senior academic with research and teaching responsibilities.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(190,'Role','created','App\\Models\\Acl\\Role','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"IT Manager\", \"slug\": \"it-manager\", \"guard_name\": \"web\", \"description\": \"Oversees IT infrastructure and strategy.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(191,'Role','created','App\\Models\\Acl\\Role','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Admissions Officer\", \"slug\": \"admissions-officer\", \"guard_name\": \"web\", \"description\": \"Handles applications and enrollment.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(192,'Role','created','App\\Models\\Acl\\Role','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Accountant\", \"slug\": \"accountant\", \"guard_name\": \"web\", \"description\": \"Manages finances of the institution.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(193,'Role','created','App\\Models\\Acl\\Role','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Bursar\", \"slug\": \"bursar\", \"guard_name\": \"web\", \"description\": \"Manages finances of the institution.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(194,'Role','created','App\\Models\\Acl\\Role','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"HR Officer\", \"slug\": \"hr-officer\", \"guard_name\": \"web\", \"description\": \"Handles staff recruitment and welfare.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(195,'Role','created','App\\Models\\Acl\\Role','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Administrative Assistant\", \"slug\": \"administrative-assistant\", \"guard_name\": \"web\", \"description\": \"Provides administrative support.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(196,'Role','created','App\\Models\\Acl\\Role','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Student Affairs Officer\", \"slug\": \"student-affairs-officer\", \"guard_name\": \"web\", \"description\": \"Supports student welfare and activities.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(197,'Role','created','App\\Models\\Acl\\Role','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"IT Support Technician\", \"slug\": \"it-support-technician\", \"guard_name\": \"web\", \"description\": \"Provides technical support.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(198,'Role','created','App\\Models\\Acl\\Role','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Lab Technician\", \"slug\": \"lab-technician\", \"guard_name\": \"web\", \"description\": \"Prepares and maintains lab equipment.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(199,'Role','created','App\\Models\\Acl\\Role','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Librarian\", \"slug\": \"librarian\", \"guard_name\": \"web\", \"description\": \"Manages library resources and services.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(200,'Role','created','App\\Models\\Acl\\Role','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Maintenance Staff\", \"slug\": \"maintenance-staff\", \"guard_name\": \"web\", \"description\": \"Ensures facility maintenance.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(201,'Role','created','App\\Models\\Acl\\Role','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Security Officer\", \"slug\": \"security-officer\", \"guard_name\": \"web\", \"description\": \"Maintains safety and security.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(202,'Role','created','App\\Models\\Acl\\Role','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Custodian\", \"slug\": \"custodian\", \"guard_name\": \"web\", \"description\": \"Responsible for cleaning and maintenance.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(203,'Role','created','App\\Models\\Acl\\Role','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Grounds Keeper\", \"slug\": \"grounds-keeper\", \"guard_name\": \"web\", \"description\": \"Maintains outdoor areas.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(204,'Role','created','App\\Models\\Acl\\Role','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Transport Officer\", \"slug\": \"transport-officer\", \"guard_name\": \"web\", \"description\": \"Manages institutional transport.\", \"role_group_id\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(205,'Role','created','App\\Models\\Acl\\Role','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Student\", \"slug\": \"student\", \"guard_name\": \"web\", \"description\": \"Learner enrolled in the institution.\", \"role_group_id\": 7}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(206,'Gender','created','App\\Models\\Shared\\Gender','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Male\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(207,'Gender','created','App\\Models\\Shared\\Gender','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Female\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(208,'IdType','created','App\\Models\\Shared\\IdType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Zimbabwean National ID\", \"description\": \"A valid Zimbabwean National Identification Number issued by the Registrar General’s Office.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(209,'IdType','created','App\\Models\\Shared\\IdType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Foreign Passport Number\", \"description\": \"A valid passport number issued by a foreign government, subject to verification and approval.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(210,'Title','created','App\\Models\\Shared\\Title','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Mr\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(211,'Title','created','App\\Models\\Shared\\Title','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Mrs\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(212,'Title','created','App\\Models\\Shared\\Title','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Miss\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(213,'Title','created','App\\Models\\Shared\\Title','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Dr\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(214,'Title','created','App\\Models\\Shared\\Title','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Prof\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(215,'Race','created','App\\Models\\Shared\\Race','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"African\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(216,'Race','created','App\\Models\\Shared\\Race','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Black\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(217,'Race','created','App\\Models\\Shared\\Race','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"White\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(218,'Race','created','App\\Models\\Shared\\Race','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Colored\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(219,'Race','created','App\\Models\\Shared\\Race','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Indian\", \"description\": null}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(220,'Status','created','App\\Models\\Shared\\Status','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Active\", \"description\": \"Currently active and in use\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(221,'Status','created','App\\Models\\Shared\\Status','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Waiting Approval\", \"description\": \"Pending approval from an authority\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(222,'Status','created','App\\Models\\Shared\\Status','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Inactive\", \"description\": \"Not currently active\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(223,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Draft / Incomplete\", \"position\": 1, \"description\": \"Application started but not submitted.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(224,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Submitted\", \"position\": 2, \"description\": \"Application has been submitted and is awaiting review.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(225,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"In Review\", \"position\": 4, \"description\": \"Application is currently under review by staff.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(226,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting Requirements\", \"position\": 5, \"description\": \"Additional documents or info required.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(227,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Awaiting Application Fee Payment\", \"position\": 3, \"description\": \"Pending payment of application or registration fees.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(228,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Interview Scheduled\", \"position\": 6, \"description\": \"Interview has been scheduled with the applicant.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(229,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Interview Completed\", \"position\": 7, \"description\": \"Interview has been completed and is under consideration.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(230,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Decision Pending\", \"position\": 8, \"description\": \"A final admission decision is being made.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(231,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Accepted / Offer Made\", \"position\": 9, \"description\": \"Offer has been made to the applicant.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(232,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Waitlisted\", \"position\": 10, \"description\": \"Applicant has been waitlisted.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(233,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Rejected\", \"position\": 11, \"description\": \"Application has been rejected.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(234,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Offer Accepted\", \"position\": 12, \"description\": \"Offer has been accepted by the applicant.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(235,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Offer Declined\", \"position\": 13, \"description\": \"Applicant declined the offer.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(236,'WorkflowStep','created','App\\Models\\Shared\\WorkflowStep','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Enrolled / Registered\", \"position\": 14, \"description\": \"Applicant has enrolled and completed registration.\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(237,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"send-email\", \"title\": \"Send Email\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(238,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"create-payment-link\", \"title\": \"Create Payment Link\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(239,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"request-documents\", \"title\": \"Request Documents\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(240,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"verify-identity\", \"title\": \"Verify Identity\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(241,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"mark-step-complete\", \"title\": \"Mark Step Complete\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(242,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',6,NULL,NULL,'{\"attributes\": {\"slug\": \"revert-step\", \"title\": \"Revert Step\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(243,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',7,NULL,NULL,'{\"attributes\": {\"slug\": \"upload-receipt\", \"title\": \"Upload Receipt\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(244,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',8,NULL,NULL,'{\"attributes\": {\"slug\": \"add-internal-note\", \"title\": \"Add Internal Note\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(245,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',9,NULL,NULL,'{\"attributes\": {\"slug\": \"notify-applicant\", \"title\": \"Notify Applicant\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(246,'WorkflowStepAction','created','App\\Models\\Shared\\WorkflowStepAction','created',10,NULL,NULL,'{\"attributes\": {\"slug\": \"assign-staff\", \"title\": \"Assign Staff\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(247,'User','created','App\\Models\\Users\\User','created',1,NULL,NULL,'{\"attributes\": {\"email\": \"penstejdevelopers@gmail.com\", \"password\": \"$2y$12$noqxHBM3mMedCyT75Ikl4.sZ9XCh/t7qh4ymGmqEeHX426kklDt8u\", \"avatar_id\": null, \"last_name\": \"Administrator\", \"status_id\": 1, \"tenant_id\": 1, \"first_name\": \"Super\", \"login_count\": 0, \"middle_name\": \"\", \"phone_number\": \"+27788104809\", \"last_login_at\": null, \"email_verified_at\": \"2025-07-07T06:22:10.000000Z\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(248,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Email\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(249,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Sms\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(250,'CommunicationMethod','created','App\\Models\\Shared\\CommunicationMethod','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Phone\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(251,'Country','created','App\\Models\\Shared\\Country','created',1,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Afghanistan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(252,'Country','created','App\\Models\\Shared\\Country','created',2,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Albania\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(253,'Country','created','App\\Models\\Shared\\Country','created',3,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Algeria\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(254,'Country','created','App\\Models\\Shared\\Country','created',4,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Andorra\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(255,'Country','created','App\\Models\\Shared\\Country','created',5,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Angola\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(256,'Country','created','App\\Models\\Shared\\Country','created',6,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Antigua and Barbuda\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(257,'Country','created','App\\Models\\Shared\\Country','created',7,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Argentina\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(258,'Country','created','App\\Models\\Shared\\Country','created',8,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Armenia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(259,'Country','created','App\\Models\\Shared\\Country','created',9,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Australia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(260,'Country','created','App\\Models\\Shared\\Country','created',10,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Azerbaijan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(261,'Country','created','App\\Models\\Shared\\Country','created',11,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bahamas\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(262,'Country','created','App\\Models\\Shared\\Country','created',12,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bahrain\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(263,'Country','created','App\\Models\\Shared\\Country','created',13,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bangladesh\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(264,'Country','created','App\\Models\\Shared\\Country','created',14,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Barbados\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(265,'Country','created','App\\Models\\Shared\\Country','created',15,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belgium\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(266,'Country','created','App\\Models\\Shared\\Country','created',16,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belize\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(267,'Country','created','App\\Models\\Shared\\Country','created',17,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Benin\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(268,'Country','created','App\\Models\\Shared\\Country','created',18,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bhutan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(269,'Country','created','App\\Models\\Shared\\Country','created',19,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bolivia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(270,'Country','created','App\\Models\\Shared\\Country','created',20,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bosnia and Herzegovina\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(271,'Country','created','App\\Models\\Shared\\Country','created',21,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Botswana\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(272,'Country','created','App\\Models\\Shared\\Country','created',22,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Brazil\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(273,'Country','created','App\\Models\\Shared\\Country','created',23,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Brunei\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(274,'Country','created','App\\Models\\Shared\\Country','created',24,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Bulgaria\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(275,'Country','created','App\\Models\\Shared\\Country','created',25,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Burkina Faso\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(276,'Country','created','App\\Models\\Shared\\Country','created',26,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Burundi\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(277,'Country','created','App\\Models\\Shared\\Country','created',27,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Chile\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(278,'Country','created','App\\Models\\Shared\\Country','created',28,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Colombia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(279,'Country','created','App\\Models\\Shared\\Country','created',29,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Comoros\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(280,'Country','created','App\\Models\\Shared\\Country','created',30,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Costa Rica\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(281,'Country','created','App\\Models\\Shared\\Country','created',31,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Democratic Republic of the Congo\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(282,'Country','created','App\\Models\\Shared\\Country','created',32,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Denmark\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(283,'Country','created','App\\Models\\Shared\\Country','created',33,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Djibouti\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(284,'Country','created','App\\Models\\Shared\\Country','created',34,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Dominica\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(285,'Country','created','App\\Models\\Shared\\Country','created',35,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Dominican Republic\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(286,'Country','created','App\\Models\\Shared\\Country','created',36,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Germany\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(287,'Country','created','App\\Models\\Shared\\Country','created',37,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ecuador\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(288,'Country','created','App\\Models\\Shared\\Country','created',38,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Egypt\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(289,'Country','created','App\\Models\\Shared\\Country','created',39,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Equatorial Guinea\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(290,'Country','created','App\\Models\\Shared\\Country','created',40,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"El Salvador\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(291,'Country','created','App\\Models\\Shared\\Country','created',41,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Eritrea\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(292,'Country','created','App\\Models\\Shared\\Country','created',42,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Estonia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(293,'Country','created','App\\Models\\Shared\\Country','created',43,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ethiopia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(294,'Country','created','App\\Models\\Shared\\Country','created',44,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Fiji\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(295,'Country','created','App\\Models\\Shared\\Country','created',45,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Philippines\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(296,'Country','created','App\\Models\\Shared\\Country','created',46,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Finland\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(297,'Country','created','App\\Models\\Shared\\Country','created',47,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"France\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(298,'Country','created','App\\Models\\Shared\\Country','created',48,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Gabon\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(299,'Country','created','App\\Models\\Shared\\Country','created',49,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Gambia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(300,'Country','created','App\\Models\\Shared\\Country','created',50,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Georgia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(301,'Country','created','App\\Models\\Shared\\Country','created',51,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ghana\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(302,'Country','created','App\\Models\\Shared\\Country','created',52,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Grenada\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(303,'Country','created','App\\Models\\Shared\\Country','created',53,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Greece\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(304,'Country','created','App\\Models\\Shared\\Country','created',54,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guatemala\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(305,'Country','created','App\\Models\\Shared\\Country','created',55,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guinea\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(306,'Country','created','App\\Models\\Shared\\Country','created',56,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guinea-Bissau\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(307,'Country','created','App\\Models\\Shared\\Country','created',57,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Guyana\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(308,'Country','created','App\\Models\\Shared\\Country','created',58,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Haiti\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(309,'Country','created','App\\Models\\Shared\\Country','created',59,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Honduras\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(310,'Country','created','App\\Models\\Shared\\Country','created',60,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Hungary\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(311,'Country','created','App\\Models\\Shared\\Country','created',61,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ireland\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(312,'Country','created','App\\Models\\Shared\\Country','created',62,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"India\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(313,'Country','created','App\\Models\\Shared\\Country','created',63,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Indonesia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(314,'Country','created','App\\Models\\Shared\\Country','created',64,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iran\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(315,'Country','created','App\\Models\\Shared\\Country','created',65,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iraq\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(316,'Country','created','App\\Models\\Shared\\Country','created',66,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Israel\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(317,'Country','created','App\\Models\\Shared\\Country','created',67,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Italy\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(318,'Country','created','App\\Models\\Shared\\Country','created',68,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Côte d’Ivoire\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(319,'Country','created','App\\Models\\Shared\\Country','created',69,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Jamaica\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(320,'Country','created','App\\Models\\Shared\\Country','created',70,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Japan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(321,'Country','created','App\\Models\\Shared\\Country','created',71,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Yemen\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(322,'Country','created','App\\Models\\Shared\\Country','created',72,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Jordan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(323,'Country','created','App\\Models\\Shared\\Country','created',73,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cape Verde Islands\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(324,'Country','created','App\\Models\\Shared\\Country','created',74,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cambodia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(325,'Country','created','App\\Models\\Shared\\Country','created',75,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cameroon\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(326,'Country','created','App\\Models\\Shared\\Country','created',76,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Canada\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(327,'Country','created','App\\Models\\Shared\\Country','created',77,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kazakhstan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(328,'Country','created','App\\Models\\Shared\\Country','created',78,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Qatar\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(329,'Country','created','App\\Models\\Shared\\Country','created',79,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kenya\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(330,'Country','created','App\\Models\\Shared\\Country','created',80,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kyrgyzstan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(331,'Country','created','App\\Models\\Shared\\Country','created',81,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kiribati\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(332,'Country','created','App\\Models\\Shared\\Country','created',82,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Kuwait\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(333,'Country','created','App\\Models\\Shared\\Country','created',83,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Croatia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(334,'Country','created','App\\Models\\Shared\\Country','created',84,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cuba\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(335,'Country','created','App\\Models\\Shared\\Country','created',85,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Laos\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(336,'Country','created','App\\Models\\Shared\\Country','created',86,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Latvia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(337,'Country','created','App\\Models\\Shared\\Country','created',87,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lesotho\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(338,'Country','created','App\\Models\\Shared\\Country','created',88,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lebanon\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(339,'Country','created','App\\Models\\Shared\\Country','created',89,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Liberia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(340,'Country','created','App\\Models\\Shared\\Country','created',90,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Libya\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(341,'Country','created','App\\Models\\Shared\\Country','created',91,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Liechtenstein\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(342,'Country','created','App\\Models\\Shared\\Country','created',92,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Lithuania\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(343,'Country','created','App\\Models\\Shared\\Country','created',93,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Luxembourg\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(344,'Country','created','App\\Models\\Shared\\Country','created',94,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Madagascar\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(345,'Country','created','App\\Models\\Shared\\Country','created',95,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malawi\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(346,'Country','created','App\\Models\\Shared\\Country','created',96,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malaysia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(347,'Country','created','App\\Models\\Shared\\Country','created',97,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Maldives\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(348,'Country','created','App\\Models\\Shared\\Country','created',98,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mali\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(349,'Country','created','App\\Models\\Shared\\Country','created',99,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Malta\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(350,'Country','created','App\\Models\\Shared\\Country','created',100,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Morocco\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(351,'Country','created','App\\Models\\Shared\\Country','created',101,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Marshall Islands\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(352,'Country','created','App\\Models\\Shared\\Country','created',102,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mauritania\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(353,'Country','created','App\\Models\\Shared\\Country','created',103,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mauritius\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(354,'Country','created','App\\Models\\Shared\\Country','created',104,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mexico\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(355,'Country','created','App\\Models\\Shared\\Country','created',105,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Myanmar (Burma)\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(356,'Country','created','App\\Models\\Shared\\Country','created',106,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Micronesia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(357,'Country','created','App\\Models\\Shared\\Country','created',107,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Moldova\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(358,'Country','created','App\\Models\\Shared\\Country','created',108,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Monaco\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(359,'Country','created','App\\Models\\Shared\\Country','created',109,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mongolia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(360,'Country','created','App\\Models\\Shared\\Country','created',110,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Montenegro\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(361,'Country','created','App\\Models\\Shared\\Country','created',111,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Mozambique\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(362,'Country','created','App\\Models\\Shared\\Country','created',112,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Namibia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(363,'Country','created','App\\Models\\Shared\\Country','created',113,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nauru\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(364,'Country','created','App\\Models\\Shared\\Country','created',114,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nepal\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(365,'Country','created','App\\Models\\Shared\\Country','created',115,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Netherlands\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(366,'Country','created','App\\Models\\Shared\\Country','created',116,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"New Zealand\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(367,'Country','created','App\\Models\\Shared\\Country','created',117,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nicaragua\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(368,'Country','created','App\\Models\\Shared\\Country','created',118,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Niger\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(369,'Country','created','App\\Models\\Shared\\Country','created',119,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Nigeria\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(370,'Country','created','App\\Models\\Shared\\Country','created',120,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"North Korea\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(371,'Country','created','App\\Models\\Shared\\Country','created',121,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Norway\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(372,'Country','created','App\\Models\\Shared\\Country','created',122,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Ukraine\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(373,'Country','created','App\\Models\\Shared\\Country','created',123,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uzbekistan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(374,'Country','created','App\\Models\\Shared\\Country','created',124,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Oman\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(375,'Country','created','App\\Models\\Shared\\Country','created',125,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Austria\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(376,'Country','created','App\\Models\\Shared\\Country','created',126,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"East Timor\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(377,'Country','created','App\\Models\\Shared\\Country','created',127,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Pakistan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(378,'Country','created','App\\Models\\Shared\\Country','created',128,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Palau\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(379,'Country','created','App\\Models\\Shared\\Country','created',129,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Panama\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(380,'Country','created','App\\Models\\Shared\\Country','created',130,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Papua New Guinea\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(381,'Country','created','App\\Models\\Shared\\Country','created',131,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Paraguay\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(382,'Country','created','App\\Models\\Shared\\Country','created',132,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Peru\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(383,'Country','created','App\\Models\\Shared\\Country','created',133,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Pole/Poland\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(384,'Country','created','App\\Models\\Shared\\Country','created',134,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Portugal\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(385,'Country','created','App\\Models\\Shared\\Country','created',135,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Republic of the Congo\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(386,'Country','created','App\\Models\\Shared\\Country','created',136,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Macedonia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(387,'Country','created','App\\Models\\Shared\\Country','created',137,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Romania\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(388,'Country','created','App\\Models\\Shared\\Country','created',138,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Russia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(389,'Country','created','App\\Models\\Shared\\Country','created',139,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Rwanda\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(390,'Country','created','App\\Models\\Shared\\Country','created',140,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Samoa\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(391,'Country','created','App\\Models\\Shared\\Country','created',141,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"San Marino\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(392,'Country','created','App\\Models\\Shared\\Country','created',142,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Saudi Arabia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(393,'Country','created','App\\Models\\Shared\\Country','created',143,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"São Tomé and Principe\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(394,'Country','created','App\\Models\\Shared\\Country','created',144,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Senegal\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(395,'Country','created','App\\Models\\Shared\\Country','created',145,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Central African Republic\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(396,'Country','created','App\\Models\\Shared\\Country','created',146,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Serbia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(397,'Country','created','App\\Models\\Shared\\Country','created',147,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Seychelles\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(398,'Country','created','App\\Models\\Shared\\Country','created',148,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"China\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(399,'Country','created','App\\Models\\Shared\\Country','created',149,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sierra Leone\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(400,'Country','created','App\\Models\\Shared\\Country','created',150,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Singapore\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(401,'Country','created','App\\Models\\Shared\\Country','created',151,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Cyprus\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(402,'Country','created','App\\Models\\Shared\\Country','created',152,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Syria\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(403,'Country','created','App\\Models\\Shared\\Country','created',153,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Slovakia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(404,'Country','created','App\\Models\\Shared\\Country','created',154,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Slovenia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(405,'Country','created','App\\Models\\Shared\\Country','created',155,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sudan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(406,'Country','created','App\\Models\\Shared\\Country','created',156,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Solomon Islands\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(407,'Country','created','App\\Models\\Shared\\Country','created',157,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Somalia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(408,'Country','created','App\\Models\\Shared\\Country','created',158,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Spain\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(409,'Country','created','App\\Models\\Shared\\Country','created',159,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sri Lanka\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(410,'Country','created','App\\Models\\Shared\\Country','created',160,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Saint Kitts and Nevis\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(411,'Country','created','App\\Models\\Shared\\Country','created',161,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"St. Lucia\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(412,'Country','created','App\\Models\\Shared\\Country','created',162,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"St. Vincent and the Grenadines\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(413,'Country','created','App\\Models\\Shared\\Country','created',163,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"South Africa\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(414,'Country','created','App\\Models\\Shared\\Country','created',164,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Suid-Korea/South Korea\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(415,'Country','created','App\\Models\\Shared\\Country','created',165,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"South Sudan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(416,'Country','created','App\\Models\\Shared\\Country','created',166,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Suriname\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(417,'Country','created','App\\Models\\Shared\\Country','created',167,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Swaziland\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(418,'Country','created','App\\Models\\Shared\\Country','created',168,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Sweden\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(419,'Country','created','App\\Models\\Shared\\Country','created',169,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Switzerland\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(420,'Country','created','App\\Models\\Shared\\Country','created',170,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tajikistan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(421,'Country','created','App\\Models\\Shared\\Country','created',171,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Taiwan\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(422,'Country','created','App\\Models\\Shared\\Country','created',172,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tanzania\"}}',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10'),(423,'Country','created','App\\Models\\Shared\\Country','created',173,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Thailand\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(424,'Country','created','App\\Models\\Shared\\Country','created',174,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tonga\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(425,'Country','created','App\\Models\\Shared\\Country','created',175,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Togo\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(426,'Country','created','App\\Models\\Shared\\Country','created',176,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Trinidad and Tobago\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(427,'Country','created','App\\Models\\Shared\\Country','created',177,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Chad\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(428,'Country','created','App\\Models\\Shared\\Country','created',178,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Czech Republic\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(429,'Country','created','App\\Models\\Shared\\Country','created',179,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tunisia\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(430,'Country','created','App\\Models\\Shared\\Country','created',180,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Turkmenistan\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(431,'Country','created','App\\Models\\Shared\\Country','created',181,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Turkey\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(432,'Country','created','App\\Models\\Shared\\Country','created',182,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Tuvalu\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(433,'Country','created','App\\Models\\Shared\\Country','created',183,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uganda\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(434,'Country','created','App\\Models\\Shared\\Country','created',184,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Uruguay\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(435,'Country','created','App\\Models\\Shared\\Country','created',185,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Vanuatu\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(436,'Country','created','App\\Models\\Shared\\Country','created',186,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Venezuela\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(437,'Country','created','App\\Models\\Shared\\Country','created',187,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United Arab Emirates\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(438,'Country','created','App\\Models\\Shared\\Country','created',188,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United Kingdom\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(439,'Country','created','App\\Models\\Shared\\Country','created',189,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"United States of America\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(440,'Country','created','App\\Models\\Shared\\Country','created',190,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Vietnam\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(441,'Country','created','App\\Models\\Shared\\Country','created',191,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Belarus\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(442,'Country','created','App\\Models\\Shared\\Country','created',192,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Iceland\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(443,'Country','created','App\\Models\\Shared\\Country','created',193,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Zambia\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(444,'Country','created','App\\Models\\Shared\\Country','created',194,NULL,NULL,'{\"attributes\": {\"flag\": null, \"name\": \"Zimbabwe\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(445,'Religion','created','App\\Models\\Shared\\Religion','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Christianity\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(446,'Religion','created','App\\Models\\Shared\\Religion','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"African Traditional Religion\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(447,'Religion','created','App\\Models\\Shared\\Religion','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Islam\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(448,'Religion','created','App\\Models\\Shared\\Religion','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Hinduism\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(449,'Religion','created','App\\Models\\Shared\\Religion','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Buddhism\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(450,'Religion','created','App\\Models\\Shared\\Religion','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Judaism\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(451,'Religion','created','App\\Models\\Shared\\Religion','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Other Religions\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(452,'Religion','created','App\\Models\\Shared\\Religion','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Religiously Unaffiliated\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(453,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Monthly\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(454,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Annually\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(455,'PaymentFrequency','created','App\\Models\\Shared\\PaymentFrequency','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Once off\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(456,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Credit Card\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(457,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Cash Payment\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(458,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Debit Order\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(459,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"EFT\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(460,'PaymentMethod','created','App\\Models\\Shared\\PaymentMethod','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Stop Order\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(461,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"1\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(462,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"2\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(463,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"3\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(464,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"4\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(465,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"5\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(466,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"6\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(467,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"7\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(468,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',8,NULL,NULL,'{\"attributes\": {\"title\": \"8\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(469,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',9,NULL,NULL,'{\"attributes\": {\"title\": \"9\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(470,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',10,NULL,NULL,'{\"attributes\": {\"title\": \"10\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(471,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',11,NULL,NULL,'{\"attributes\": {\"title\": \"11\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(472,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',12,NULL,NULL,'{\"attributes\": {\"title\": \"12\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(473,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',13,NULL,NULL,'{\"attributes\": {\"title\": \"13\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(474,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',14,NULL,NULL,'{\"attributes\": {\"title\": \"14\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(475,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',15,NULL,NULL,'{\"attributes\": {\"title\": \"15\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(476,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',16,NULL,NULL,'{\"attributes\": {\"title\": \"16\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(477,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',17,NULL,NULL,'{\"attributes\": {\"title\": \"17\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(478,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',18,NULL,NULL,'{\"attributes\": {\"title\": \"18\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(479,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',19,NULL,NULL,'{\"attributes\": {\"title\": \"19\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(480,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',20,NULL,NULL,'{\"attributes\": {\"title\": \"20\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(481,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',21,NULL,NULL,'{\"attributes\": {\"title\": \"21\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(482,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',22,NULL,NULL,'{\"attributes\": {\"title\": \"22\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(483,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',23,NULL,NULL,'{\"attributes\": {\"title\": \"23\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(484,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',24,NULL,NULL,'{\"attributes\": {\"title\": \"24\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(485,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',25,NULL,NULL,'{\"attributes\": {\"title\": \"25\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(486,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',26,NULL,NULL,'{\"attributes\": {\"title\": \"26\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(487,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',27,NULL,NULL,'{\"attributes\": {\"title\": \"27\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(488,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',28,NULL,NULL,'{\"attributes\": {\"title\": \"28\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(489,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',29,NULL,NULL,'{\"attributes\": {\"title\": \"29\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(490,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',30,NULL,NULL,'{\"attributes\": {\"title\": \"30\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(491,'PaymentDay','created','App\\Models\\Shared\\PaymentDay','created',31,NULL,NULL,'{\"attributes\": {\"title\": \"31\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(492,'Language','created','App\\Models\\Shared\\Language','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"English\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(493,'Province','created','App\\Models\\Shared\\Province','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Bulawayo\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(494,'Province','created','App\\Models\\Shared\\Province','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Harare\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(495,'Province','created','App\\Models\\Shared\\Province','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Manicaland\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(496,'Province','created','App\\Models\\Shared\\Province','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland Central\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(497,'Province','created','App\\Models\\Shared\\Province','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland East\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(498,'Province','created','App\\Models\\Shared\\Province','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Mashonaland West\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(499,'Province','created','App\\Models\\Shared\\Province','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"Masvingo\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(500,'Province','created','App\\Models\\Shared\\Province','created',8,NULL,NULL,'{\"attributes\": {\"title\": \"Matebeleland North\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(501,'Province','created','App\\Models\\Shared\\Province','created',9,NULL,NULL,'{\"attributes\": {\"title\": \"Matebeleland South\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(502,'Province','created','App\\Models\\Shared\\Province','created',10,NULL,NULL,'{\"attributes\": {\"title\": \"Midlands\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(503,'Province','created','App\\Models\\Shared\\Province','created',11,NULL,NULL,'{\"attributes\": {\"title\": \"Unknown Province\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(504,'District','created','App\\Models\\Shared\\District','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Bulawayo\", \"description\": null, \"province_id\": 1}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(505,'District','created','App\\Models\\Shared\\District','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Chitungwiza\", \"description\": null, \"province_id\": 2}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(506,'District','created','App\\Models\\Shared\\District','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Harare\", \"description\": null, \"province_id\": 2}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(507,'District','created','App\\Models\\Shared\\District','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Buhera\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(508,'District','created','App\\Models\\Shared\\District','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Chimanimani\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(509,'District','created','App\\Models\\Shared\\District','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Chipinge\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(510,'District','created','App\\Models\\Shared\\District','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Makoni\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(511,'District','created','App\\Models\\Shared\\District','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Mutare\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(512,'District','created','App\\Models\\Shared\\District','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Mutasa\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(513,'District','created','App\\Models\\Shared\\District','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Nyanga\", \"description\": null, \"province_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(514,'District','created','App\\Models\\Shared\\District','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Bindura\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(515,'District','created','App\\Models\\Shared\\District','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Guruve\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(516,'District','created','App\\Models\\Shared\\District','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Mazowe\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(517,'District','created','App\\Models\\Shared\\District','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Mbire\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(518,'District','created','App\\Models\\Shared\\District','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Mount Darwin\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(519,'District','created','App\\Models\\Shared\\District','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Muzarabani\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(520,'District','created','App\\Models\\Shared\\District','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Rushinga\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(521,'District','created','App\\Models\\Shared\\District','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Shamva\", \"description\": null, \"province_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(522,'District','created','App\\Models\\Shared\\District','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Chikomba\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(523,'District','created','App\\Models\\Shared\\District','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Goromonzi\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(524,'District','created','App\\Models\\Shared\\District','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Marondera\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(525,'District','created','App\\Models\\Shared\\District','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Mudzi\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(526,'District','created','App\\Models\\Shared\\District','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Murehwa\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(527,'District','created','App\\Models\\Shared\\District','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Mutoko\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(528,'District','created','App\\Models\\Shared\\District','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Seke\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(529,'District','created','App\\Models\\Shared\\District','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"UMP (Uzumba-Maramba-Pfungwe)\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(530,'District','created','App\\Models\\Shared\\District','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Wedza (Hwedza)\", \"description\": null, \"province_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(531,'District','created','App\\Models\\Shared\\District','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Chegutu\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(532,'District','created','App\\Models\\Shared\\District','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Hurungwe\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(533,'District','created','App\\Models\\Shared\\District','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Kariba\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(534,'District','created','App\\Models\\Shared\\District','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Makonde\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(535,'District','created','App\\Models\\Shared\\District','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Mhondoro-Ngezi\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(536,'District','created','App\\Models\\Shared\\District','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"Sanyati\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(537,'District','created','App\\Models\\Shared\\District','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"Zvimba\", \"description\": null, \"province_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(538,'District','created','App\\Models\\Shared\\District','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"Bikita\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(539,'District','created','App\\Models\\Shared\\District','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"Chiredzi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(540,'District','created','App\\Models\\Shared\\District','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"Chivi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(541,'District','created','App\\Models\\Shared\\District','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"Gutu\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(542,'District','created','App\\Models\\Shared\\District','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"Masvingo\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(543,'District','created','App\\Models\\Shared\\District','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"Mwenezi\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(544,'District','created','App\\Models\\Shared\\District','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"Zaka\", \"description\": null, \"province_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(545,'District','created','App\\Models\\Shared\\District','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"Binga\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(546,'District','created','App\\Models\\Shared\\District','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"Bubi\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(547,'District','created','App\\Models\\Shared\\District','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"Hwange\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(548,'District','created','App\\Models\\Shared\\District','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"Lupane\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(549,'District','created','App\\Models\\Shared\\District','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"Nkayi\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(550,'District','created','App\\Models\\Shared\\District','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"Tsholotsho\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(551,'District','created','App\\Models\\Shared\\District','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"Umguza\", \"description\": null, \"province_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(552,'District','created','App\\Models\\Shared\\District','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"Beitbridge\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(553,'District','created','App\\Models\\Shared\\District','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"Bulilima\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(554,'District','created','App\\Models\\Shared\\District','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"Gwanda\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(555,'District','created','App\\Models\\Shared\\District','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"Insiza\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(556,'District','created','App\\Models\\Shared\\District','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"Mangwe\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(557,'District','created','App\\Models\\Shared\\District','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"Matobo\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(558,'District','created','App\\Models\\Shared\\District','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"Umzingwane\", \"description\": null, \"province_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(559,'District','created','App\\Models\\Shared\\District','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"Chirumhanzu\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(560,'District','created','App\\Models\\Shared\\District','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"Gokwe North\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(561,'District','created','App\\Models\\Shared\\District','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"Gokwe South\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(562,'District','created','App\\Models\\Shared\\District','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"Gweru\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(563,'District','created','App\\Models\\Shared\\District','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"Kwekwe\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(564,'District','created','App\\Models\\Shared\\District','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"Mberengwa\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(565,'District','created','App\\Models\\Shared\\District','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"Shurugwi\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(566,'District','created','App\\Models\\Shared\\District','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"Zvishavane\", \"description\": null, \"province_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(567,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Person\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(568,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Company\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(569,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Church\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(570,'SponsorType','created','App\\Models\\Shared\\SponsorType','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Other Organization\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(571,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Divorced\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(572,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Engaged\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(573,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Married\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(574,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Single\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(575,'MaritalStatus','created','App\\Models\\Shared\\MaritalStatus','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Widowed\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(576,'AddressType','created','App\\Models\\Shared\\AddressType','created',1,NULL,NULL,'{\"attributes\": {\"slug\": \"business\", \"title\": \"Business\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(577,'AddressType','created','App\\Models\\Shared\\AddressType','created',2,NULL,NULL,'{\"attributes\": {\"slug\": \"complex\", \"title\": \"Complex\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(578,'AddressType','created','App\\Models\\Shared\\AddressType','created',3,NULL,NULL,'{\"attributes\": {\"slug\": \"home\", \"title\": \"Home\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(579,'AddressType','created','App\\Models\\Shared\\AddressType','created',4,NULL,NULL,'{\"attributes\": {\"slug\": \"physical\", \"title\": \"Physical\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(580,'AddressType','created','App\\Models\\Shared\\AddressType','created',5,NULL,NULL,'{\"attributes\": {\"slug\": \"postal\", \"title\": \"Postal\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(581,'Course','created','App\\Models\\Institution\\Course','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Beauty Therapy\", \"position\": 1, \"description\": \"Applied Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(582,'Course','created','App\\Models\\Institution\\Course','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Cosmetology\", \"position\": 2, \"description\": \"Applied Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(583,'Course','created','App\\Models\\Institution\\Course','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Hairdressing\", \"position\": 3, \"description\": \"Applied Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(584,'Course','created','App\\Models\\Institution\\Course','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Industrial Clothing Design and Construction Design\", \"position\": 4, \"description\": \"Applied Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(585,'Course','created','App\\Models\\Institution\\Course','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Biological Technology\", \"position\": 5, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(586,'Course','created','App\\Models\\Institution\\Course','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Chemical Technology\", \"position\": 6, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(587,'Course','created','App\\Models\\Institution\\Course','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Chemical Engineering\", \"position\": 7, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(588,'Course','created','App\\Models\\Institution\\Course','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Chemical Technology\", \"position\": 8, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(589,'Course','created','App\\Models\\Institution\\Course','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Food Science\", \"position\": 9, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(590,'Course','created','App\\Models\\Institution\\Course','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Horticulture\", \"position\": 10, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(591,'Course','created','App\\Models\\Institution\\Course','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Laboratory Technology\", \"position\": 11, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(592,'Course','created','App\\Models\\Institution\\Course','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Metallurgical Assaying\", \"position\": 12, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(593,'Course','created','App\\Models\\Institution\\Course','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Pharmaceutical Technology\", \"position\": 13, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(594,'Course','created','App\\Models\\Institution\\Course','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Polymer Technology\", \"position\": 14, \"description\": \"Applied Science Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(595,'Course','created','App\\Models\\Institution\\Course','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Automobile Electrics And Electronics\", \"position\": 15, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(596,'Course','created','App\\Models\\Institution\\Course','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Engineering\", \"position\": 16, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(597,'Course','created','App\\Models\\Institution\\Course','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Precision Machining\", \"position\": 17, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(598,'Course','created','App\\Models\\Institution\\Course','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"Diesel Plant Fitting\", \"position\": 18, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(599,'Course','created','App\\Models\\Institution\\Course','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Cycle Machining\", \"position\": 19, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(600,'Course','created','App\\Models\\Institution\\Course','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Vehicle Body Repairs\", \"position\": 20, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(601,'Course','created','App\\Models\\Institution\\Course','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Motor Vehicle Mechanics\", \"position\": 21, \"description\": \"Automotive\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(602,'Course','created','App\\Models\\Institution\\Course','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Accountancy\", \"position\": 22, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(603,'Course','created','App\\Models\\Institution\\Course','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Banking and Finance\", \"position\": 23, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(604,'Course','created','App\\Models\\Institution\\Course','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Health Services Management\", \"position\": 24, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(605,'Course','created','App\\Models\\Institution\\Course','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Human Resources Management\", \"position\": 25, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(606,'Course','created','App\\Models\\Institution\\Course','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Pensions & Investments Management\", \"position\": 26, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(607,'Course','created','App\\Models\\Institution\\Course','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Purchasing & Supply Management\", \"position\": 27, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(608,'Course','created','App\\Models\\Institution\\Course','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Sales & Marketing Management\", \"position\": 28, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(609,'Course','created','App\\Models\\Institution\\Course','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Trainers Diploma In Education\", \"position\": 29, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(610,'Course','created','App\\Models\\Institution\\Course','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Transport & Logistics Management\", \"position\": 30, \"description\": \"Commerce\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(611,'Course','created','App\\Models\\Institution\\Course','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Architectural Technology\", \"position\": 31, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(612,'Course','created','App\\Models\\Institution\\Course','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Cartography & Geo-Visualization Theory Technology\", \"position\": 32, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(613,'Course','created','App\\Models\\Institution\\Course','created',33,NULL,NULL,'{\"attributes\": {\"name\": \"Civil Engineering\", \"position\": 33, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(614,'Course','created','App\\Models\\Institution\\Course','created',34,NULL,NULL,'{\"attributes\": {\"name\": \"Quantity Surveying\", \"position\": 34, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(615,'Course','created','App\\Models\\Institution\\Course','created',35,NULL,NULL,'{\"attributes\": {\"name\": \"Surveying and Geomatics\", \"position\": 35, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(616,'Course','created','App\\Models\\Institution\\Course','created',36,NULL,NULL,'{\"attributes\": {\"name\": \"Urban And Regional Planning\", \"position\": 36, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(617,'Course','created','App\\Models\\Institution\\Course','created',37,NULL,NULL,'{\"attributes\": {\"name\": \"Valuation & Estate Management\", \"position\": 37, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(618,'Course','created','App\\Models\\Institution\\Course','created',38,NULL,NULL,'{\"attributes\": {\"name\": \"Water Resources & Irrigation Engineering\", \"position\": 38, \"description\": \"Civil Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(619,'Course','created','App\\Models\\Institution\\Course','created',39,NULL,NULL,'{\"attributes\": {\"name\": \"Building Technology\", \"position\": 39, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(620,'Course','created','App\\Models\\Institution\\Course','created',40,NULL,NULL,'{\"attributes\": {\"name\": \"Carpentry and Joinery\", \"position\": 40, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(621,'Course','created','App\\Models\\Institution\\Course','created',41,NULL,NULL,'{\"attributes\": {\"name\": \"Construction Engineering\", \"position\": 41, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(622,'Course','created','App\\Models\\Institution\\Course','created',42,NULL,NULL,'{\"attributes\": {\"name\": \"Painting and Decorating Technology\", \"position\": 42, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(623,'Course','created','App\\Models\\Institution\\Course','created',43,NULL,NULL,'{\"attributes\": {\"name\": \"Plumbing and Drain Laying\", \"position\": 43, \"description\": \"Construction Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(624,'Course','created','App\\Models\\Institution\\Course','created',44,NULL,NULL,'{\"attributes\": {\"name\": \"Computer Systems\", \"position\": 44, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(625,'Course','created','App\\Models\\Institution\\Course','created',45,NULL,NULL,'{\"attributes\": {\"name\": \"Domestic and Industrial Solar Installation\", \"position\": 45, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(626,'Course','created','App\\Models\\Institution\\Course','created',46,NULL,NULL,'{\"attributes\": {\"name\": \"Electrical Power Engineering\", \"position\": 46, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(627,'Course','created','App\\Models\\Institution\\Course','created',47,NULL,NULL,'{\"attributes\": {\"name\": \"Electronic Communication Systems\", \"position\": 47, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(628,'Course','created','App\\Models\\Institution\\Course','created',48,NULL,NULL,'{\"attributes\": {\"name\": \"Instrumentation and Control Systems\", \"position\": 48, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(629,'Course','created','App\\Models\\Institution\\Course','created',49,NULL,NULL,'{\"attributes\": {\"name\": \"Microwave and Radar\", \"position\": 49, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(630,'Course','created','App\\Models\\Institution\\Course','created',50,NULL,NULL,'{\"attributes\": {\"name\": \"Mobile and Satellite Communication\", \"position\": 50, \"description\": \"Electrical Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(631,'Course','created','App\\Models\\Institution\\Course','created',51,NULL,NULL,'{\"attributes\": {\"name\": \"Information Technology\", \"position\": 51, \"description\": \"Information Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(632,'Course','created','App\\Models\\Institution\\Course','created',52,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Computer Engineering\", \"position\": 52, \"description\": \"Information Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(633,'Course','created','App\\Models\\Institution\\Course','created',53,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Computing and Information Systems\", \"position\": 53, \"description\": \"Information Technology\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(634,'Course','created','App\\Models\\Institution\\Course','created',54,NULL,NULL,'{\"attributes\": {\"name\": \"Library and Information Sciences\", \"position\": 54, \"description\": \"Library and Information Systems\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(635,'Course','created','App\\Models\\Institution\\Course','created',55,NULL,NULL,'{\"attributes\": {\"name\": \"Records Management and Information Sciences\", \"position\": 55, \"description\": \"Library and Information Systems\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(636,'Course','created','App\\Models\\Institution\\Course','created',56,NULL,NULL,'{\"attributes\": {\"name\": \"Draughting and Design Technology\", \"position\": 56, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(637,'Course','created','App\\Models\\Institution\\Course','created',57,NULL,NULL,'{\"attributes\": {\"name\": \"Fabrication Engineering\", \"position\": 57, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(638,'Course','created','App\\Models\\Institution\\Course','created',58,NULL,NULL,'{\"attributes\": {\"name\": \"Machine Shop Engineering\", \"position\": 58, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(639,'Course','created','App\\Models\\Institution\\Course','created',59,NULL,NULL,'{\"attributes\": {\"name\": \"Mechanical Engineering\", \"position\": 59, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(640,'Course','created','App\\Models\\Institution\\Course','created',60,NULL,NULL,'{\"attributes\": {\"name\": \"Millwright Works\", \"position\": 60, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(641,'Course','created','App\\Models\\Institution\\Course','created',61,NULL,NULL,'{\"attributes\": {\"name\": \"Plant Engineering\", \"position\": 61, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(642,'Course','created','App\\Models\\Institution\\Course','created',62,NULL,NULL,'{\"attributes\": {\"name\": \"Production Engineering\", \"position\": 62, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(643,'Course','created','App\\Models\\Institution\\Course','created',63,NULL,NULL,'{\"attributes\": {\"name\": \"Refrigeration and Air Conditioning\", \"position\": 63, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(644,'Course','created','App\\Models\\Institution\\Course','created',64,NULL,NULL,'{\"attributes\": {\"name\": \"Vehicle Body Building\", \"position\": 64, \"description\": \"Mechanical and Production Engineering\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(645,'Course','created','App\\Models\\Institution\\Course','created',65,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Art and Design\", \"position\": 65, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(646,'Course','created','App\\Models\\Institution\\Course','created',66,NULL,NULL,'{\"attributes\": {\"name\": \"Design For Print\", \"position\": 66, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(647,'Course','created','App\\Models\\Institution\\Course','created',67,NULL,NULL,'{\"attributes\": {\"name\": \"Fine Arts\", \"position\": 67, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(648,'Course','created','App\\Models\\Institution\\Course','created',68,NULL,NULL,'{\"attributes\": {\"name\": \"Machine Printing\", \"position\": 68, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(649,'Course','created','App\\Models\\Institution\\Course','created',69,NULL,NULL,'{\"attributes\": {\"name\": \"Multimedia\", \"position\": 69, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(650,'Course','created','App\\Models\\Institution\\Course','created',70,NULL,NULL,'{\"attributes\": {\"name\": \"Packaging Machine Minding\", \"position\": 70, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(651,'Course','created','App\\Models\\Institution\\Course','created',71,NULL,NULL,'{\"attributes\": {\"name\": \"Photography\", \"position\": 71, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(652,'Course','created','App\\Models\\Institution\\Course','created',72,NULL,NULL,'{\"attributes\": {\"name\": \"Printing, Finishing and Converting\", \"position\": 72, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(653,'Course','created','App\\Models\\Institution\\Course','created',73,NULL,NULL,'{\"attributes\": {\"name\": \"Print Finishing Technology\", \"position\": 73, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(654,'Course','created','App\\Models\\Institution\\Course','created',74,NULL,NULL,'{\"attributes\": {\"name\": \"Print Production Technology\", \"position\": 74, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(655,'Course','created','App\\Models\\Institution\\Course','created',75,NULL,NULL,'{\"attributes\": {\"name\": \"Print Origination Technology\", \"position\": 75, \"description\": \"Printing and Graphic Arts\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(656,'Course','created','App\\Models\\Institution\\Course','created',76,NULL,NULL,'{\"attributes\": {\"name\": \"Broadcast Journalism\", \"position\": 76, \"description\": \"Mass Communication\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(657,'Course','created','App\\Models\\Institution\\Course','created',77,NULL,NULL,'{\"attributes\": {\"name\": \"Mass Communication\", \"position\": 77, \"description\": \"Mass Communication\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(658,'Course','created','App\\Models\\Institution\\Course','created',78,NULL,NULL,'{\"attributes\": {\"name\": \"Print Journalism\", \"position\": 78, \"description\": \"Mass Communication\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(659,'Course','created','App\\Models\\Institution\\Course','created',79,NULL,NULL,'{\"attributes\": {\"name\": \"Public Relations\", \"position\": 79, \"description\": \"Mass Communication\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(660,'Course','created','App\\Models\\Institution\\Course','created',80,NULL,NULL,'{\"attributes\": {\"name\": \"Office Management\", \"position\": 80, \"description\": \"Office Management\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(661,'Course','created','App\\Models\\Institution\\Course','created',81,NULL,NULL,'{\"attributes\": {\"name\": \"Bakery Technology and Management\", \"position\": 81, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(662,'Course','created','App\\Models\\Institution\\Course','created',82,NULL,NULL,'{\"attributes\": {\"name\": \"Culinary Arts\", \"position\": 82, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(663,'Course','created','App\\Models\\Institution\\Course','created',83,NULL,NULL,'{\"attributes\": {\"name\": \"Professional Cookery\", \"position\": 83, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(664,'Course','created','App\\Models\\Institution\\Course','created',84,NULL,NULL,'{\"attributes\": {\"name\": \"Tourism and Hospitality Management\", \"position\": 84, \"description\": \"Tourism and Hospitality\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(665,'Department','created','App\\Models\\Institution\\Department','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Applied Arts\", \"position\": 1, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(666,'Department','created','App\\Models\\Institution\\Department','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Automotive Engineering\", \"position\": 2, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(667,'Department','created','App\\Models\\Institution\\Department','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Business & Management Studies\", \"position\": 3, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(668,'Department','created','App\\Models\\Institution\\Department','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Civil Engineering\", \"position\": 4, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(669,'Department','created','App\\Models\\Institution\\Department','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Construction Engineering\", \"position\": 5, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(670,'Department','created','App\\Models\\Institution\\Department','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Electrical Engineering\", \"position\": 6, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(671,'Department','created','App\\Models\\Institution\\Department','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Information Communication Technology\", \"position\": 7, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(672,'Department','created','App\\Models\\Institution\\Department','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Library & Info Sciences\", \"position\": 8, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(673,'Department','created','App\\Models\\Institution\\Department','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Mass Communication\", \"position\": 9, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(674,'Department','created','App\\Models\\Institution\\Department','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Mechanical & Production Engineering\", \"position\": 10, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(675,'Department','created','App\\Models\\Institution\\Department','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Office Management\", \"position\": 11, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(676,'Department','created','App\\Models\\Institution\\Department','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Printing And Graphics Arts\", \"position\": 12, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(677,'Department','created','App\\Models\\Institution\\Department','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"Science Technology\", \"position\": 13, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(678,'Department','created','App\\Models\\Institution\\Department','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Tourism And Hospitality\", \"position\": 14, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(679,'Division','created','App\\Models\\Institution\\Division','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Business\", \"position\": 1, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(680,'Division','created','App\\Models\\Institution\\Division','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Management\", \"position\": 2, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(681,'Division','created','App\\Models\\Institution\\Division','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Pedagogics\", \"position\": 3, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(682,'Grade','created','App\\Models\\Institution\\Grade','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"A\", \"position\": 1, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(683,'Grade','created','App\\Models\\Institution\\Grade','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"B\", \"position\": 2, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(684,'Grade','created','App\\Models\\Institution\\Grade','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"C\", \"position\": 3, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(685,'Grade','created','App\\Models\\Institution\\Grade','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"D\", \"position\": 4, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(686,'Grade','created','App\\Models\\Institution\\Grade','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"E\", \"position\": 5, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(687,'Grade','created','App\\Models\\Institution\\Grade','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"U\", \"position\": 6, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(688,'Level','created','App\\Models\\Institution\\Level','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 3\", \"position\": 1, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(689,'Level','created','App\\Models\\Institution\\Level','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 4\", \"position\": 2, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(690,'Level','created','App\\Models\\Institution\\Level','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 5\", \"position\": 3, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(691,'Level','created','App\\Models\\Institution\\Level','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"ABMA Level 6\", \"position\": 4, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(692,'Level','created','App\\Models\\Institution\\Level','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"NC\", \"position\": 5, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(693,'Level','created','App\\Models\\Institution\\Level','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"ND\", \"position\": 6, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(694,'Level','created','App\\Models\\Institution\\Level','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"HND\", \"position\": 7, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(695,'Level','created','App\\Models\\Institution\\Level','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"BTECH\", \"position\": 8, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(696,'Level','created','App\\Models\\Institution\\Level','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"SDP\", \"position\": 9, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(697,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Primary school\", \"position\": 1, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(698,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Secondary school\", \"position\": 2, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(699,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Advanced Level\", \"position\": 3, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(700,'AcademicLevel','created','App\\Models\\Shared\\AcademicLevel','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Tertiary Level\", \"position\": 4, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(701,'Relationship','created','App\\Models\\Shared\\Relationship','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Parent\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(702,'Relationship','created','App\\Models\\Shared\\Relationship','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Spouse\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(703,'Relationship','created','App\\Models\\Shared\\Relationship','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Guardian\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(704,'Subject','created','App\\Models\\Institution\\Subject','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Accounts\", \"position\": 1, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(705,'Subject','created','App\\Models\\Institution\\Subject','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Agriculture\", \"position\": 2, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(706,'Subject','created','App\\Models\\Institution\\Subject','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Art\", \"position\": 3, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(707,'Subject','created','App\\Models\\Institution\\Subject','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Bible Knowledge\", \"position\": 4, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(708,'Subject','created','App\\Models\\Institution\\Subject','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Building Studies\", \"position\": 5, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(709,'Subject','created','App\\Models\\Institution\\Subject','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Business and Enterprise Skills\", \"position\": 6, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(710,'Subject','created','App\\Models\\Institution\\Subject','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Business Studies\", \"position\": 7, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(711,'Subject','created','App\\Models\\Institution\\Subject','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Chinese\", \"position\": 8, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(712,'Subject','created','App\\Models\\Institution\\Subject','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Commerce\", \"position\": 9, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(713,'Subject','created','App\\Models\\Institution\\Subject','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Computer Science\", \"position\": 10, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(714,'Subject','created','App\\Models\\Institution\\Subject','created',11,NULL,NULL,'{\"attributes\": {\"name\": \"Design and Technology\", \"position\": 11, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(715,'Subject','created','App\\Models\\Institution\\Subject','created',12,NULL,NULL,'{\"attributes\": {\"name\": \"Economics\", \"position\": 12, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(716,'Subject','created','App\\Models\\Institution\\Subject','created',13,NULL,NULL,'{\"attributes\": {\"name\": \"English\", \"position\": 13, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(717,'Subject','created','App\\Models\\Institution\\Subject','created',14,NULL,NULL,'{\"attributes\": {\"name\": \"Fashion and Fabrics\", \"position\": 14, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(718,'Subject','created','App\\Models\\Institution\\Subject','created',15,NULL,NULL,'{\"attributes\": {\"name\": \"Food and Nutrition\", \"position\": 15, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(719,'Subject','created','App\\Models\\Institution\\Subject','created',16,NULL,NULL,'{\"attributes\": {\"name\": \"French\", \"position\": 16, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(720,'Subject','created','App\\Models\\Institution\\Subject','created',17,NULL,NULL,'{\"attributes\": {\"name\": \"Geography\", \"position\": 17, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(721,'Subject','created','App\\Models\\Institution\\Subject','created',18,NULL,NULL,'{\"attributes\": {\"name\": \"German\", \"position\": 18, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(722,'Subject','created','App\\Models\\Institution\\Subject','created',19,NULL,NULL,'{\"attributes\": {\"name\": \"History\", \"position\": 19, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(723,'Subject','created','App\\Models\\Institution\\Subject','created',20,NULL,NULL,'{\"attributes\": {\"name\": \"Integrated Science\", \"position\": 20, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(724,'Subject','created','App\\Models\\Institution\\Subject','created',21,NULL,NULL,'{\"attributes\": {\"name\": \"Literature in English\", \"position\": 21, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(725,'Subject','created','App\\Models\\Institution\\Subject','created',22,NULL,NULL,'{\"attributes\": {\"name\": \"Mathematics\", \"position\": 22, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(726,'Subject','created','App\\Models\\Institution\\Subject','created',23,NULL,NULL,'{\"attributes\": {\"name\": \"Metal Technology and Design\", \"position\": 23, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(727,'Subject','created','App\\Models\\Institution\\Subject','created',24,NULL,NULL,'{\"attributes\": {\"name\": \"Music\", \"position\": 24, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(728,'Subject','created','App\\Models\\Institution\\Subject','created',25,NULL,NULL,'{\"attributes\": {\"name\": \"Ndebele\", \"position\": 25, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(729,'Subject','created','App\\Models\\Institution\\Subject','created',26,NULL,NULL,'{\"attributes\": {\"name\": \"Physical Education, Sport and Mass Displays\", \"position\": 26, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(730,'Subject','created','App\\Models\\Institution\\Subject','created',27,NULL,NULL,'{\"attributes\": {\"name\": \"Religious Studies\", \"position\": 27, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(731,'Subject','created','App\\Models\\Institution\\Subject','created',28,NULL,NULL,'{\"attributes\": {\"name\": \"Shona\", \"position\": 28, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(732,'Subject','created','App\\Models\\Institution\\Subject','created',29,NULL,NULL,'{\"attributes\": {\"name\": \"Spanish\", \"position\": 29, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(733,'Subject','created','App\\Models\\Institution\\Subject','created',30,NULL,NULL,'{\"attributes\": {\"name\": \"Technical Graphics\", \"position\": 30, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(734,'Subject','created','App\\Models\\Institution\\Subject','created',31,NULL,NULL,'{\"attributes\": {\"name\": \"Wood Technology and Design\", \"position\": 31, \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(735,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Full Time\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(736,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Part Time\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(737,'ModeOfStudy','created','App\\Models\\Institution\\ModeOfStudy','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Block Release\", \"description\": null}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(738,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',1,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 1}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(739,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',2,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 2}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(740,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',3,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 3}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(741,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',4,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 4}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(742,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',5,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 5}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(743,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',6,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 6}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(744,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',7,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 7}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(745,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',8,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 8}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(746,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',9,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 9}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(747,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',10,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 10}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(748,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',11,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 11}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(749,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',12,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 12}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(750,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',13,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 13}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(751,'InstitutionDepartment','created','App\\Models\\Institution\\InstitutionDepartment','created',14,NULL,NULL,'{\"attributes\": {\"tenant_id\": 1, \"description\": null, \"department_id\": 14}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(752,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Full time\", \"description\": \"Full-time employment (35–40+ hours/week with benefits)\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(753,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Part time\", \"description\": \"Part-time employment (less than 35 hours/week)\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(754,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Temporary\", \"description\": \"Temporary or contract-based employment\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(755,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Freelance\", \"description\": \"Freelance or self-employed contractor work\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(756,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Intern\", \"description\": \"Internship or apprenticeship (temporary, for experience)\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(757,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Casual\", \"description\": \"Casual work (on-call or irregular hours)\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(758,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"Seasonal\", \"description\": \"Seasonal employment (e.g. holiday or harvest periods)\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11'),(759,'EmploymentType','created','App\\Models\\Shared\\EmploymentType','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Remote\", \"description\": \"Remote or telecommuting work (offsite)\"}}',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11');
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
INSERT INTO `address_types` VALUES (1,'Business','business',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Complex','complex',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Home','home',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Physical','physical',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Postal','postal',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `communication_methods` VALUES (1,'Email','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Sms','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'Phone','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `countries` VALUES (1,'Afghanistan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Albania',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'Algeria',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(4,'Andorra',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(5,'Angola',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(6,'Antigua and Barbuda',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(7,'Argentina',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(8,'Armenia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(9,'Australia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(10,'Azerbaijan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(11,'Bahamas',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(12,'Bahrain',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(13,'Bangladesh',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(14,'Barbados',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(15,'Belgium',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(16,'Belize',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(17,'Benin',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(18,'Bhutan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(19,'Bolivia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(20,'Bosnia and Herzegovina',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(21,'Botswana',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(22,'Brazil',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(23,'Brunei',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(24,'Bulgaria',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(25,'Burkina Faso',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(26,'Burundi',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(27,'Chile',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(28,'Colombia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(29,'Comoros',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(30,'Costa Rica',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(31,'Democratic Republic of the Congo',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(32,'Denmark',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(33,'Djibouti',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(34,'Dominica',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(35,'Dominican Republic',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(36,'Germany',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(37,'Ecuador',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(38,'Egypt',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(39,'Equatorial Guinea',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(40,'El Salvador',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(41,'Eritrea',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(42,'Estonia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(43,'Ethiopia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(44,'Fiji',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(45,'Philippines',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(46,'Finland',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(47,'France',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(48,'Gabon',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(49,'Gambia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(50,'Georgia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(51,'Ghana',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(52,'Grenada',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(53,'Greece',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(54,'Guatemala',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(55,'Guinea',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(56,'Guinea-Bissau',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(57,'Guyana',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(58,'Haiti',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(59,'Honduras',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(60,'Hungary',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(61,'Ireland',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(62,'India',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(63,'Indonesia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(64,'Iran',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(65,'Iraq',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(66,'Israel',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(67,'Italy',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(68,'Côte d’Ivoire',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(69,'Jamaica',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(70,'Japan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(71,'Yemen',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(72,'Jordan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(73,'Cape Verde Islands',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(74,'Cambodia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(75,'Cameroon',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(76,'Canada',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(77,'Kazakhstan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(78,'Qatar',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(79,'Kenya',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(80,'Kyrgyzstan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(81,'Kiribati',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(82,'Kuwait',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(83,'Croatia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(84,'Cuba',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(85,'Laos',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(86,'Latvia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(87,'Lesotho',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(88,'Lebanon',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(89,'Liberia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(90,'Libya',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(91,'Liechtenstein',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(92,'Lithuania',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(93,'Luxembourg',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(94,'Madagascar',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(95,'Malawi',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(96,'Malaysia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(97,'Maldives',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(98,'Mali',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(99,'Malta',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(100,'Morocco',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(101,'Marshall Islands',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(102,'Mauritania',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(103,'Mauritius',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(104,'Mexico',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(105,'Myanmar (Burma)',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(106,'Micronesia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(107,'Moldova',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(108,'Monaco',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(109,'Mongolia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(110,'Montenegro',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(111,'Mozambique',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(112,'Namibia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(113,'Nauru',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(114,'Nepal',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(115,'Netherlands',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(116,'New Zealand',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(117,'Nicaragua',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(118,'Niger',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(119,'Nigeria',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(120,'North Korea',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(121,'Norway',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(122,'Ukraine',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(123,'Uzbekistan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(124,'Oman',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(125,'Austria',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(126,'East Timor',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(127,'Pakistan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(128,'Palau',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(129,'Panama',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(130,'Papua New Guinea',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(131,'Paraguay',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(132,'Peru',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(133,'Pole/Poland',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(134,'Portugal',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(135,'Republic of the Congo',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(136,'Macedonia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(137,'Romania',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(138,'Russia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(139,'Rwanda',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(140,'Samoa',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(141,'San Marino',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(142,'Saudi Arabia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(143,'São Tomé and Principe',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(144,'Senegal',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(145,'Central African Republic',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(146,'Serbia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(147,'Seychelles',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(148,'China',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(149,'Sierra Leone',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(150,'Singapore',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(151,'Cyprus',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(152,'Syria',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(153,'Slovakia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(154,'Slovenia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(155,'Sudan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(156,'Solomon Islands',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(157,'Somalia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(158,'Spain',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(159,'Sri Lanka',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(160,'Saint Kitts and Nevis',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(161,'St. Lucia',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(162,'St. Vincent and the Grenadines',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(163,'South Africa',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(164,'Suid-Korea/South Korea',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(165,'South Sudan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(166,'Suriname',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(167,'Swaziland',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(168,'Sweden',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(169,'Switzerland',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(170,'Tajikistan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(171,'Taiwan',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(172,'Tanzania',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(173,'Thailand',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(174,'Tonga',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(175,'Togo',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(176,'Trinidad and Tobago',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(177,'Chad',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(178,'Czech Republic',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(179,'Tunisia',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(180,'Turkmenistan',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(181,'Turkey',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(182,'Tuvalu',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(183,'Uganda',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(184,'Uruguay',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(185,'Vanuatu',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(186,'Venezuela',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(187,'United Arab Emirates',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(188,'United Kingdom',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(189,'United States of America',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(190,'Vietnam',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(191,'Belarus',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(192,'Iceland',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(193,'Zambia',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(194,'Zimbabwe',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `courses` VALUES (1,'Beauty Therapy',1,'Applied Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Cosmetology',2,'Applied Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Hairdressing',3,'Applied Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Industrial Clothing Design and Construction Design',4,'Applied Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Applied Biological Technology',5,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Applied Chemical Technology',6,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Chemical Engineering',7,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Chemical Technology',8,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'Food Science',9,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,'Horticulture',10,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,'Laboratory Technology',11,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(12,'Metallurgical Assaying',12,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(13,'Pharmaceutical Technology',13,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(14,'Polymer Technology',14,'Applied Science Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(15,'Automobile Electrics And Electronics',15,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(16,'Automotive Engineering',16,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(17,'Automotive Precision Machining',17,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(18,'Diesel Plant Fitting',18,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(19,'Motor Cycle Machining',19,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(20,'Motor Vehicle Body Repairs',20,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(21,'Motor Vehicle Mechanics',21,'Automotive','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(22,'Accountancy',22,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(23,'Banking and Finance',23,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(24,'Health Services Management',24,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(25,'Human Resources Management',25,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(26,'Pensions & Investments Management',26,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(27,'Purchasing & Supply Management',27,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(28,'Sales & Marketing Management',28,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(29,'Trainers Diploma In Education',29,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(30,'Transport & Logistics Management',30,'Commerce','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(31,'Architectural Technology',31,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(32,'Cartography & Geo-Visualization Theory Technology',32,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(33,'Civil Engineering',33,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(34,'Quantity Surveying',34,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(35,'Surveying and Geomatics',35,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(36,'Urban And Regional Planning',36,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(37,'Valuation & Estate Management',37,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(38,'Water Resources & Irrigation Engineering',38,'Civil Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(39,'Building Technology',39,'Construction Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(40,'Carpentry and Joinery',40,'Construction Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(41,'Construction Engineering',41,'Construction Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(42,'Painting and Decorating Technology',42,'Construction Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(43,'Plumbing and Drain Laying',43,'Construction Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(44,'Computer Systems',44,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(45,'Domestic and Industrial Solar Installation',45,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(46,'Electrical Power Engineering',46,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(47,'Electronic Communication Systems',47,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(48,'Instrumentation and Control Systems',48,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(49,'Microwave and Radar',49,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(50,'Mobile and Satellite Communication',50,'Electrical Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(51,'Information Technology',51,'Information Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(52,'Professional Computer Engineering',52,'Information Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(53,'Professional Computing and Information Systems',53,'Information Technology','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(54,'Library and Information Sciences',54,'Library and Information Systems','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(55,'Records Management and Information Sciences',55,'Library and Information Systems','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(56,'Draughting and Design Technology',56,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(57,'Fabrication Engineering',57,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(58,'Machine Shop Engineering',58,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(59,'Mechanical Engineering',59,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(60,'Millwright Works',60,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(61,'Plant Engineering',61,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(62,'Production Engineering',62,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(63,'Refrigeration and Air Conditioning',63,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(64,'Vehicle Body Building',64,'Mechanical and Production Engineering','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(65,'Applied Art and Design',65,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(66,'Design For Print',66,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(67,'Fine Arts',67,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(68,'Machine Printing',68,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(69,'Multimedia',69,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(70,'Packaging Machine Minding',70,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(71,'Photography',71,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(72,'Printing, Finishing and Converting',72,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(73,'Print Finishing Technology',73,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(74,'Print Production Technology',74,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(75,'Print Origination Technology',75,'Printing and Graphic Arts','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(76,'Broadcast Journalism',76,'Mass Communication','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(77,'Mass Communication',77,'Mass Communication','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(78,'Print Journalism',78,'Mass Communication','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(79,'Public Relations',79,'Mass Communication','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(80,'Office Management',80,'Office Management','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(81,'Bakery Technology and Management',81,'Tourism and Hospitality','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(82,'Culinary Arts',82,'Tourism and Hospitality','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(83,'Professional Cookery',83,'Tourism and Hospitality','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(84,'Tourism and Hospitality Management',84,'Tourism and Hospitality','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `departments` VALUES (1,'Applied Arts',1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Automotive Engineering',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Business & Management Studies',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Civil Engineering',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Construction Engineering',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Electrical Engineering',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Information Communication Technology',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Library & Info Sciences',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'Mass Communication',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,'Mechanical & Production Engineering',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,'Office Management',11,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(12,'Printing And Graphics Arts',12,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(13,'Science Technology',13,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(14,'Tourism And Hospitality',14,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `districts` VALUES (1,'Bulawayo',1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Chitungwiza',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Harare',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Buhera',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Chimanimani',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Chipinge',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Makoni',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Mutare',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'Mutasa',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,'Nyanga',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,'Bindura',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(12,'Guruve',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(13,'Mazowe',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(14,'Mbire',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(15,'Mount Darwin',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(16,'Muzarabani',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(17,'Rushinga',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(18,'Shamva',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(19,'Chikomba',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(20,'Goromonzi',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(21,'Marondera',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(22,'Mudzi',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(23,'Murehwa',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(24,'Mutoko',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(25,'Seke',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(26,'UMP (Uzumba-Maramba-Pfungwe)',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(27,'Wedza (Hwedza)',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(28,'Chegutu',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(29,'Hurungwe',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(30,'Kariba',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(31,'Makonde',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(32,'Mhondoro-Ngezi',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(33,'Sanyati',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(34,'Zvimba',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(35,'Bikita',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(36,'Chiredzi',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(37,'Chivi',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(38,'Gutu',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(39,'Masvingo',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(40,'Mwenezi',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(41,'Zaka',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(42,'Binga',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(43,'Bubi',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(44,'Hwange',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(45,'Lupane',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(46,'Nkayi',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(47,'Tsholotsho',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(48,'Umguza',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(49,'Beitbridge',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(50,'Bulilima',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(51,'Gwanda',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(52,'Insiza',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(53,'Mangwe',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(54,'Matobo',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(55,'Umzingwane',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(56,'Chirumhanzu',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(57,'Gokwe North',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(58,'Gokwe South',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(59,'Gweru',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(60,'Kwekwe',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(61,'Mberengwa',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(62,'Shurugwi',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(63,'Zvishavane',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `divisions` VALUES (1,'Business',1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Management',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Pedagogics',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `employment_types` VALUES (1,'Full time','Full-time employment (35–40+ hours/week with benefits)','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Part time','Part-time employment (less than 35 hours/week)','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Temporary','Temporary or contract-based employment','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Freelance','Freelance or self-employed contractor work','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Intern','Internship or apprenticeship (temporary, for experience)','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Casual','Casual work (on-call or irregular hours)','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Seasonal','Seasonal employment (e.g. holiday or harvest periods)','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Remote','Remote or telecommuting work (offsite)','2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `genders` VALUES (1,'Male',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Female',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `grades` VALUES (1,'A',1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'B',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'C',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'D',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'E',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'U',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `id_types` VALUES (1,'Zimbabwean National ID',1,'A valid Zimbabwean National Identification Number issued by the Registrar General’s Office.','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Foreign Passport Number',0,'A valid passport number issued by a foreign government, subject to verification and approval.','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institution_departments`
--

/*!40000 ALTER TABLE `institution_departments` DISABLE KEYS */;
INSERT INTO `institution_departments` VALUES (1,1,1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,1,2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,1,3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,1,4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,1,5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,1,6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,1,7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,1,8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,1,9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,1,10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,1,11,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(12,1,12,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(13,1,13,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(14,1,14,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `languages` VALUES (1,'English',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `levels` VALUES (1,'ABMA Level 3',1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'ABMA Level 4',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'ABMA Level 5',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'ABMA Level 6',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'NC',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'ND',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'HND',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'BTECH',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'SDP',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `marital_statuses` VALUES (1,'Divorced',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Engaged',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Married',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Single',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Widowed',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_create_tenants_table',1),(2,'0001_00_19_183342_create_races_table',1),(3,'0001_01_00_000000_create_statuses_table',1),(4,'0001_01_00_125713_create_genders_table',1),(5,'0001_01_00_183404_create_titles_table',1),(6,'0001_01_01_000000_create_users_table',1),(7,'0001_01_01_000001_create_cache_table',1),(8,'0001_01_01_000002_create_jobs_table',1),(9,'2024_11_06_174210_create_role_groups_table',1),(10,'2024_12_10_073103_create_media_table',1),(11,'2024_12_10_073229_create_activity_log_table',1),(12,'2024_12_10_073230_add_event_column_to_activity_log_table',1),(13,'2024_12_10_073231_add_batch_uuid_column_to_activity_log_table',1),(14,'2024_12_10_091905_create_modules_table',1),(15,'2024_12_10_112501_create_permission_tables',1),(16,'2025_01_18_202508_create_communication_methods_table',1),(17,'2025_01_18_222026_create_countries_table',1),(18,'2025_01_19_101127_create_payment_days_table',1),(19,'2025_01_19_101323_create_payment_frequencies_table',1),(20,'2025_01_19_101437_create_payment_methods_table',1),(21,'2025_01_19_140446_create_languages_table',1),(22,'2025_01_19_143527_create_provinces_table',1),(23,'2025_03_20_185152_create_addresses_table',1),(24,'2025_03_20_190050_create_contacts_table',1),(25,'2025_03_22_053137_create_address_types_table',1),(26,'2025_04_25_173642_create_departments_table',1),(27,'2025_04_25_173916_create_courses_table',1),(28,'2025_04_25_174007_create_divisions_table',1),(29,'2025_04_25_174046_create_grades_table',1),(30,'2025_04_25_174107_create_levels_table',1),(31,'2025_04_25_174151_create_relationships_table',1),(32,'2025_04_25_174216_create_subjects_table',1),(33,'2025_04_25_193714_create_mode_of_studies_table',1),(34,'2025_04_27_142505_create_districts_table',1),(35,'2025_04_28_135636_create_institution_departments_table',1),(36,'2025_05_06_231759_create_department_levels_table',1),(37,'2025_05_07_152341_create_personal_access_tokens_table',1),(38,'2025_05_09_073840_create_department_courses_table',1),(39,'2025_05_13_164228_create_department_course_levels_table',1),(40,'2025_05_22_063933_create_department_level_requirements_table',1),(41,'2025_05_26_082810_create_marital_statuses_table',1),(42,'2025_06_08_181246_create_id_types_table',1),(43,'2025_06_19_045841_create_students_table',1),(44,'2025_06_19_053738_create_student_programs_table',1),(45,'2025_06_20_012032_create_next_of_kin_table',1),(46,'2025_06_21_115803_create_religions_table',1),(47,'2025_06_23_054353_create_academic_levels_table',1),(48,'2025_06_23_125237_create_sponsors_table',1),(49,'2025_06_23_132119_create_sponsor_types_table',1),(50,'2025_06_26_034105_create_academic_records_table',1),(51,'2025_06_29_085659_create_workflow_steps_table',1),(52,'2025_06_30_125235_create_intake_periods_table',1),(53,'2025_07_00_195358_create_employment_types_table',1),(54,'2025_07_02_052540_create_staff_table',1),(55,'2025_07_03_135229_create_institution_department_staff_table',1),(56,'2025_07_06_001824_create_department_application_steps_table',1),(57,'2025_07_06_082931_create_workflow_step_actions_table',1),(58,'2025_07_06_151404_create_department_workflow_steps_table',1);
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
INSERT INTO `mode_of_studies` VALUES (1,'Full Time',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Part Time',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Block Release',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `model_has_roles` VALUES (2,'App\\Models\\Users\\User',1);
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
INSERT INTO `modules` VALUES (1,'Accommodations','accommodations',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(2,'Acl','acl',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(3,'Communications','communications',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(4,'Dashboards','dashboards',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(5,'Enrolments','enrolments',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(6,'Examinations','examinations',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(7,'Institution','institution',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(8,'Other','other',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(9,'Reports','reports',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(10,'Root','root',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(11,'Settings','settings',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(12,'Shared','shared',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(13,'Students','students',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(14,'Tenants','tenants',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(15,'Users','users',NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL);
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
INSERT INTO `payment_days` VALUES (1,'1',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'2',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'3',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'4',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'5',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'6',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'7',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'8',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'9',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,'10',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,'11',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(12,'12',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(13,'13',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(14,'14',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(15,'15',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(16,'16',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(17,'17',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(18,'18',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(19,'19',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(20,'20',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(21,'21',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(22,'22',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(23,'23',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(24,'24',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(25,'25',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(26,'26',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(27,'27',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(28,'28',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(29,'29',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(30,'30',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(31,'31',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `payment_frequencies` VALUES (1,'Monthly',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Annually',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Once off',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `payment_methods` VALUES (1,'Credit Card',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Cash Payment',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Debit Order',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'EFT',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Stop Order',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `permissions` VALUES (1,'view:acl-settings',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(2,'viewAny:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(3,'view:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(4,'create:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(5,'update:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(6,'delete:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(7,'restore:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(8,'forceDelete:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(9,'import:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(10,'export:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(11,'viewAuditTrail:modules',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(12,'viewAny:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(13,'view:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(14,'create:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(15,'update:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(16,'delete:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(17,'restore:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(18,'forceDelete:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(19,'import:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(20,'export:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(21,'viewAuditTrail:roles',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(22,'viewAny:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(23,'view:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(24,'create:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(25,'update:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(26,'delete:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(27,'restore:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(28,'forceDelete:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(29,'import:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(30,'export:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(31,'viewAuditTrail:permissions',NULL,'web',2,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(32,'viewAny:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(33,'view:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(34,'create:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(35,'update:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(36,'delete:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(37,'restore:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(38,'forceDelete:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(39,'import:communications',NULL,'web',3,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(40,'export:communications',NULL,'web',3,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(41,'crud-settings:communications',NULL,'web',3,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(42,'viewAuditTrail:communications',NULL,'web',3,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(43,'viewAny:dashboards',NULL,'web',4,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(44,'view:dashboards',NULL,'web',4,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(45,'viewAny:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(46,'view:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(47,'create:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(48,'update:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(49,'delete:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(50,'restore:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(51,'forceDelete:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(52,'import:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(53,'export:reports',NULL,'web',9,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(54,'viewAny:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(55,'view:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(56,'create:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(57,'update:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(58,'delete:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(59,'restore:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(60,'forceDelete:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(61,'import:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(62,'export:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(63,'crud-settings:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(64,'viewAuditTrail:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(65,'manageOwnData:tenants',NULL,'web',14,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(66,'viewAny:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(67,'view:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(68,'create:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(69,'update:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(70,'delete:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(71,'restore:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(72,'forceDelete:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(73,'import:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(74,'export:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(75,'crud-settings:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(76,'viewAuditTrail:users',NULL,'web',15,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(77,'root:manage',NULL,'web',10,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(78,'view:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(79,'create:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(80,'update:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(81,'delete:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(82,'restore:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(83,'forceDelete:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(84,'import:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(85,'export:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(86,'viewAuditTrail:settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(87,'view:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(88,'create:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(89,'update:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(90,'delete:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(91,'restore:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(92,'forceDelete:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(93,'import:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(94,'export:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(95,'viewAuditTrail:institution-settings',NULL,'web',11,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(96,'viewAny:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(97,'view:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(98,'create:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(99,'update:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(100,'delete:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(101,'restore:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(102,'forceDelete:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(103,'import:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(104,'export:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(105,'viewAuditTrail:department-metadata',NULL,'web',7,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(106,'viewAny:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(107,'view:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(108,'create:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(109,'update:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(110,'delete:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(111,'restore:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(112,'forceDelete:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(113,'import:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(114,'export:bank-details',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(115,'viewAny:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(116,'view:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(117,'create:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(118,'update:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(119,'delete:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(120,'restore:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(121,'forceDelete:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(122,'import:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(123,'export:addresses',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(124,'viewAny:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(125,'view:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(126,'create:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(127,'update:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(128,'delete:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(129,'restore:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(130,'forceDelete:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(131,'import:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(132,'export:contacts',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(133,'viewAny:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(134,'view:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(135,'create:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(136,'update:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(137,'delete:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(138,'restore:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(139,'forceDelete:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(140,'import:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(141,'export:next-of-kins',NULL,'web',12,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(142,'viewOwnDashboard:students',NULL,'web',13,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(143,'manageOwnStudentPersonalDetails:students',NULL,'web',13,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(144,'manageOwnStudentProgramDetails:students',NULL,'web',13,'2025-07-07 04:22:09','2025-07-07 04:22:09',NULL),(145,'manageOwnStudentSponsorDetails:students',NULL,'web',13,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(146,'manageOwnStudentContactDetails:students',NULL,'web',13,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(147,'manageOwnStudentFinancialDetails:students',NULL,'web',13,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(148,'manageOwnStudentAcademicDetails:students',NULL,'web',13,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(149,'manageStudentMetadata:admin',NULL,'web',13,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `provinces` VALUES (1,'Bulawayo',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Harare',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Manicaland',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Mashonaland Central',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Mashonaland East',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Mashonaland West',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Masvingo',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Matebeleland North',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'Matebeleland South',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,'Midlands',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,'Unknown Province',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `races` VALUES (1,'African',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Black',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'White',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(4,'Colored',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(5,'Indian',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `relationships` VALUES (1,'Parent',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Spouse',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Guardian',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `religions` VALUES (1,'Christianity',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'African Traditional Religion',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Islam',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Hinduism',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Buddhism',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Judaism',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Other Religions',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Religiously Unaffiliated',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_groups`
--

/*!40000 ALTER TABLE `role_groups` DISABLE KEYS */;
INSERT INTO `role_groups` VALUES (1,'super-user','Super User','System-level user with access to all areas.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(2,'tesc','TESC','Tertiary Education Service Council (TESC) group.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(3,'executive','Executive','Executive leadership including deans, directors, or heads of departments.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(4,'academic-staff','Academic Staff','Teaching and research personnel such as lecturers and professors.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(5,'administrative-managerial-staff','Administrative & Managerial Staff','Administrative Staff (Non-Academic, Managerial) involved in management or administration.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(6,'support-service-staff','Support Service Staff','Support and Service Staff (Non-Academic, Operational) providing technical, clerical, or facility-related support.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(7,'student','Student','Registered learners in the institution.','2025-07-07 04:22:08','2025-07-07 04:22:08',NULL);
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
INSERT INTO `role_has_permissions` VALUES (1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(52,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(66,2),(67,2),(68,2),(69,2),(70,2),(71,2),(72,2),(73,2),(74,2),(75,2),(76,2),(77,2),(78,2),(79,2),(80,2),(81,2),(82,2),(83,2),(84,2),(85,2),(86,2),(87,2),(88,2),(89,2),(90,2),(91,2),(92,2),(93,2),(94,2),(95,2),(96,2),(97,2),(98,2),(99,2),(100,2),(101,2),(102,2),(103,2),(104,2),(105,2),(106,2),(107,2),(108,2),(109,2),(110,2),(111,2),(112,2),(113,2),(114,2),(115,2),(116,2),(117,2),(118,2),(119,2),(120,2),(121,2),(122,2),(123,2),(124,2),(125,2),(126,2),(127,2),(128,2),(129,2),(130,2),(131,2),(132,2),(133,2),(134,2),(135,2),(136,2),(137,2),(138,2),(139,2),(140,2),(141,2),(149,2),(142,32),(143,32),(144,32),(145,32),(146,32),(147,32),(148,32);
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super User','super-user',1,'Power user with elevated privileges for system oversight.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Super Administrator','super-administrator',1,'Has unrestricted access to all system functions.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'TESC','tesc',2,'Tertiary Education Service Council (TESC) group responsible for overseeing tertiary education policies and standards.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(4,'Principal','principal',3,'The head of the institution.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(5,'Vice Principal','vice-principal',3,'Deputy to the Principal.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(6,'Registrar','registrar',3,'Oversees academic records and administrative operations.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(7,'Selection Officer','selection-officer',3,'Manages student selection processes.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(8,'Dean','dean',3,'Leads a faculty or academic division.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(9,'Head of Division','head-of-division',3,'Leads a division and oversees departments within it.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(10,'Head of Department','head-of-department',3,'Responsible for a specific academic department.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(11,'Lecturer','lecturer',NULL,'Delivers academic content to students.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(12,'Lecturer in Charge','lecturer-in-charge',NULL,'Coordinates lecturers within a module.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(13,'Senior Lecturer','senior-lecturer',NULL,'Senior academic with additional responsibilities.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(14,'Tutor','tutor',NULL,'Supports students in tutorials and small groups.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(15,'Researcher','researcher',NULL,'Conducts academic or scientific research.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(16,'Professor','professor',NULL,'Senior academic with research and teaching responsibilities.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(17,'IT Manager','it-manager',NULL,'Oversees IT infrastructure and strategy.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(18,'Admissions Officer','admissions-officer',NULL,'Handles applications and enrollment.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(19,'Accountant','accountant',NULL,'Manages finances of the institution.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(20,'Bursar','bursar',NULL,'Manages finances of the institution.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(21,'HR Officer','hr-officer',NULL,'Handles staff recruitment and welfare.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(22,'Administrative Assistant','administrative-assistant',NULL,'Provides administrative support.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(23,'Student Affairs Officer','student-affairs-officer',NULL,'Supports student welfare and activities.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(24,'IT Support Technician','it-support-technician',NULL,'Provides technical support.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(25,'Lab Technician','lab-technician',NULL,'Prepares and maintains lab equipment.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(26,'Librarian','librarian',NULL,'Manages library resources and services.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(27,'Maintenance Staff','maintenance-staff',NULL,'Ensures facility maintenance.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(28,'Security Officer','security-officer',NULL,'Maintains safety and security.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(29,'Custodian','custodian',NULL,'Responsible for cleaning and maintenance.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(30,'Grounds Keeper','grounds-keeper',NULL,'Maintains outdoor areas.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(31,'Transport Officer','transport-officer',NULL,'Manages institutional transport.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(32,'Student','student',7,'Learner enrolled in the institution.','web','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `sponsor_types` VALUES (1,'Person',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Company',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Church',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Other Organization',NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
  UNIQUE KEY `staff_staff_id_number_unique` (`staff_id_number`),
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
INSERT INTO `statuses` VALUES (1,'Active',1,'Currently active and in use','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Waiting Approval',0,'Pending approval from an authority','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'Inactive',0,'Not currently active','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
  `id_type_id` bigint unsigned NOT NULL,
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
INSERT INTO `subjects` VALUES (1,'Accounts',1,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(2,'Agriculture',2,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(3,'Art',3,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(4,'Bible Knowledge',4,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(5,'Building Studies',5,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(6,'Business and Enterprise Skills',6,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(7,'Business Studies',7,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(8,'Chinese',8,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(9,'Commerce',9,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(10,'Computer Science',10,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(11,'Design and Technology',11,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(12,'Economics',12,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(13,'English',13,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(14,'Fashion and Fabrics',14,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(15,'Food and Nutrition',15,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(16,'French',16,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(17,'Geography',17,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(18,'German',18,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(19,'History',19,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(20,'Integrated Science',20,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(21,'Literature in English',21,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(22,'Mathematics',22,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(23,'Metal Technology and Design',23,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(24,'Music',24,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(25,'Ndebele',25,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(26,'Physical Education, Sport and Mass Displays',26,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(27,'Religious Studies',27,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(28,'Shona',28,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(29,'Spanish',29,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(30,'Technical Graphics',30,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL),(31,'Wood Technology and Design',31,NULL,'2025-07-07 04:22:11','2025-07-07 04:22:11',NULL);
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
INSERT INTO `tenants` VALUES (1,'Harare Poly',1,NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL),(2,'Penstej Systems',0,NULL,'2025-07-07 04:22:08','2025-07-07 04:22:08',NULL);
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
INSERT INTO `titles` VALUES (1,'Mr',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Mrs',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'Miss',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(4,'Dr',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(5,'Prof',NULL,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `users` VALUES (1,1,NULL,'Super','','Administrator','penstejdevelopers@gmail.com','+27788104809','2025-07-07 04:22:10','$2y$12$noqxHBM3mMedCyT75Ikl4.sZ9XCh/t7qh4ymGmqEeHX426kklDt8u',NULL,0,NULL,1,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `workflow_step_actions` VALUES (1,'send-email','Send Email','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'create-payment-link','Create Payment Link','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'request-documents','Request Documents','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(4,'verify-identity','Verify Identity','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(5,'mark-step-complete','Mark Step Complete','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(6,'revert-step','Revert Step','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(7,'upload-receipt','Upload Receipt','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(8,'add-internal-note','Add Internal Note','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(9,'notify-applicant','Notify Applicant','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(10,'assign-staff','Assign Staff','2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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
INSERT INTO `workflow_steps` VALUES (1,'Draft / Incomplete','Application started but not submitted.',1,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(2,'Submitted','Application has been submitted and is awaiting review.',2,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(3,'In Review','Application is currently under review by staff.',4,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(4,'Awaiting Requirements','Additional documents or info required.',5,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(5,'Awaiting Application Fee Payment','Pending payment of application or registration fees.',3,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(6,'Interview Scheduled','Interview has been scheduled with the applicant.',6,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(7,'Interview Completed','Interview has been completed and is under consideration.',7,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(8,'Decision Pending','A final admission decision is being made.',8,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(9,'Accepted / Offer Made','Offer has been made to the applicant.',9,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(10,'Waitlisted','Applicant has been waitlisted.',10,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(11,'Rejected','Application has been rejected.',11,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(12,'Offer Accepted','Offer has been accepted by the applicant.',12,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(13,'Offer Declined','Applicant declined the offer.',13,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL),(14,'Enrolled / Registered','Applicant has enrolled and completed registration.',14,'2025-07-07 04:22:10','2025-07-07 04:22:10',NULL);
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

-- Dump completed on 2025-07-07  8:23:13
