CREATE DATABASE  IF NOT EXISTS `hrepoly` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `hrepoly`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: hrepoly
-- ------------------------------------------------------
-- Server version	8.0.42

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

LOCK TABLES `address_types` WRITE;
/*!40000 ALTER TABLE `address_types` DISABLE KEYS */;
INSERT INTO `address_types` VALUES (1,'Business','business',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(2,'Complex','complex',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(3,'Home','home',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(4,'Physical','physical',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(5,'Postal','postal',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL);
/*!40000 ALTER TABLE `address_types` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('hrepoly_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:6:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:11:\"description\";s:1:\"d\";s:10:\"guard_name\";s:1:\"e\";s:9:\"module_id\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:132:{i:0;a:6:{s:1:\"a\";i:1;s:1:\"b\";s:17:\"view:acl-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:1;a:6:{s:1:\"a\";i:2;s:1:\"b\";s:15:\"viewAny:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:2;a:6:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"view:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:3;a:6:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"create:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:4;a:6:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"update:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:5;a:6:{s:1:\"a\";i:6;s:1:\"b\";s:14:\"delete:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:6;a:6:{s:1:\"a\";i:7;s:1:\"b\";s:15:\"restore:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:7;a:6:{s:1:\"a\";i:8;s:1:\"b\";s:19:\"forceDelete:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:8;a:6:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"import:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:9;a:6:{s:1:\"a\";i:10;s:1:\"b\";s:14:\"export:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:10;a:6:{s:1:\"a\";i:11;s:1:\"b\";s:22:\"viewAuditTrail:modules\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:11;a:6:{s:1:\"a\";i:12;s:1:\"b\";s:13:\"viewAny:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:12;a:6:{s:1:\"a\";i:13;s:1:\"b\";s:10:\"view:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:13;a:6:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"create:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:14;a:6:{s:1:\"a\";i:15;s:1:\"b\";s:12:\"update:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:15;a:6:{s:1:\"a\";i:16;s:1:\"b\";s:12:\"delete:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:16;a:6:{s:1:\"a\";i:17;s:1:\"b\";s:13:\"restore:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:17;a:6:{s:1:\"a\";i:18;s:1:\"b\";s:17:\"forceDelete:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:18;a:6:{s:1:\"a\";i:19;s:1:\"b\";s:12:\"import:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:19;a:6:{s:1:\"a\";i:20;s:1:\"b\";s:12:\"export:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:20;a:6:{s:1:\"a\";i:21;s:1:\"b\";s:20:\"viewAuditTrail:roles\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:21;a:6:{s:1:\"a\";i:22;s:1:\"b\";s:19:\"viewAny:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:22;a:6:{s:1:\"a\";i:23;s:1:\"b\";s:16:\"view:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:23;a:6:{s:1:\"a\";i:24;s:1:\"b\";s:18:\"create:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:24;a:6:{s:1:\"a\";i:25;s:1:\"b\";s:18:\"update:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:25;a:6:{s:1:\"a\";i:26;s:1:\"b\";s:18:\"delete:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:26;a:6:{s:1:\"a\";i:27;s:1:\"b\";s:19:\"restore:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:27;a:6:{s:1:\"a\";i:28;s:1:\"b\";s:23:\"forceDelete:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:28;a:6:{s:1:\"a\";i:29;s:1:\"b\";s:18:\"import:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:29;a:6:{s:1:\"a\";i:30;s:1:\"b\";s:18:\"export:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:30;a:6:{s:1:\"a\";i:31;s:1:\"b\";s:26:\"viewAuditTrail:permissions\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:2;s:1:\"r\";a:1:{i:0;i:3;}}i:31;a:6:{s:1:\"a\";i:32;s:1:\"b\";s:22:\"viewAny:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:32;a:6:{s:1:\"a\";i:33;s:1:\"b\";s:19:\"view:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:33;a:6:{s:1:\"a\";i:34;s:1:\"b\";s:21:\"create:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:34;a:6:{s:1:\"a\";i:35;s:1:\"b\";s:21:\"update:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:35;a:6:{s:1:\"a\";i:36;s:1:\"b\";s:21:\"delete:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:36;a:6:{s:1:\"a\";i:37;s:1:\"b\";s:22:\"restore:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:37;a:6:{s:1:\"a\";i:38;s:1:\"b\";s:26:\"forceDelete:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:38;a:6:{s:1:\"a\";i:39;s:1:\"b\";s:21:\"import:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:39;a:6:{s:1:\"a\";i:40;s:1:\"b\";s:21:\"export:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:40;a:6:{s:1:\"a\";i:41;s:1:\"b\";s:28:\"crud-settings:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:41;a:6:{s:1:\"a\";i:42;s:1:\"b\";s:29:\"viewAuditTrail:communications\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:3;s:1:\"r\";a:1:{i:0;i:3;}}i:42;a:6:{s:1:\"a\";i:43;s:1:\"b\";s:18:\"viewAny:dashboards\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:4;s:1:\"r\";a:1:{i:0;i:3;}}i:43;a:6:{s:1:\"a\";i:44;s:1:\"b\";s:15:\"view:dashboards\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:4;s:1:\"r\";a:1:{i:0;i:3;}}i:44;a:6:{s:1:\"a\";i:45;s:1:\"b\";s:15:\"viewAny:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:45;a:6:{s:1:\"a\";i:46;s:1:\"b\";s:12:\"view:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:46;a:6:{s:1:\"a\";i:47;s:1:\"b\";s:14:\"create:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:47;a:6:{s:1:\"a\";i:48;s:1:\"b\";s:14:\"update:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:48;a:6:{s:1:\"a\";i:49;s:1:\"b\";s:14:\"delete:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:49;a:6:{s:1:\"a\";i:50;s:1:\"b\";s:15:\"restore:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:50;a:6:{s:1:\"a\";i:51;s:1:\"b\";s:19:\"forceDelete:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:51;a:6:{s:1:\"a\";i:52;s:1:\"b\";s:14:\"import:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:52;a:6:{s:1:\"a\";i:53;s:1:\"b\";s:14:\"export:reports\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:9;s:1:\"r\";a:1:{i:0;i:3;}}i:53;a:6:{s:1:\"a\";i:54;s:1:\"b\";s:15:\"viewAny:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:54;a:6:{s:1:\"a\";i:55;s:1:\"b\";s:12:\"view:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:55;a:6:{s:1:\"a\";i:56;s:1:\"b\";s:14:\"create:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:56;a:6:{s:1:\"a\";i:57;s:1:\"b\";s:14:\"update:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:57;a:6:{s:1:\"a\";i:58;s:1:\"b\";s:14:\"delete:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:58;a:6:{s:1:\"a\";i:59;s:1:\"b\";s:15:\"restore:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:59;a:6:{s:1:\"a\";i:60;s:1:\"b\";s:19:\"forceDelete:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:60;a:6:{s:1:\"a\";i:61;s:1:\"b\";s:14:\"import:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:61;a:6:{s:1:\"a\";i:62;s:1:\"b\";s:14:\"export:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:62;a:6:{s:1:\"a\";i:63;s:1:\"b\";s:21:\"crud-settings:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:63;a:6:{s:1:\"a\";i:64;s:1:\"b\";s:22:\"viewAuditTrail:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;s:1:\"r\";a:1:{i:0;i:3;}}i:64;a:5:{s:1:\"a\";i:65;s:1:\"b\";s:21:\"manageOwnData:tenants\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:14;}i:65;a:6:{s:1:\"a\";i:66;s:1:\"b\";s:13:\"viewAny:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:66;a:6:{s:1:\"a\";i:67;s:1:\"b\";s:10:\"view:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:67;a:6:{s:1:\"a\";i:68;s:1:\"b\";s:12:\"create:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:68;a:6:{s:1:\"a\";i:69;s:1:\"b\";s:12:\"update:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:69;a:6:{s:1:\"a\";i:70;s:1:\"b\";s:12:\"delete:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:70;a:6:{s:1:\"a\";i:71;s:1:\"b\";s:13:\"restore:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:71;a:6:{s:1:\"a\";i:72;s:1:\"b\";s:17:\"forceDelete:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:72;a:6:{s:1:\"a\";i:73;s:1:\"b\";s:12:\"import:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:73;a:6:{s:1:\"a\";i:74;s:1:\"b\";s:12:\"export:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:74;a:6:{s:1:\"a\";i:75;s:1:\"b\";s:19:\"crud-settings:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:75;a:6:{s:1:\"a\";i:76;s:1:\"b\";s:20:\"viewAuditTrail:users\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:15;s:1:\"r\";a:1:{i:0;i:3;}}i:76;a:6:{s:1:\"a\";i:77;s:1:\"b\";s:11:\"root:manage\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:10;s:1:\"r\";a:1:{i:0;i:3;}}i:77;a:6:{s:1:\"a\";i:78;s:1:\"b\";s:13:\"view:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:78;a:6:{s:1:\"a\";i:79;s:1:\"b\";s:15:\"create:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:79;a:6:{s:1:\"a\";i:80;s:1:\"b\";s:15:\"update:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:80;a:6:{s:1:\"a\";i:81;s:1:\"b\";s:15:\"delete:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:81;a:6:{s:1:\"a\";i:82;s:1:\"b\";s:16:\"restore:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:82;a:6:{s:1:\"a\";i:83;s:1:\"b\";s:20:\"forceDelete:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:83;a:6:{s:1:\"a\";i:84;s:1:\"b\";s:15:\"import:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:84;a:6:{s:1:\"a\";i:85;s:1:\"b\";s:15:\"export:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:85;a:6:{s:1:\"a\";i:86;s:1:\"b\";s:23:\"viewAuditTrail:settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:86;a:6:{s:1:\"a\";i:87;s:1:\"b\";s:25:\"view:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:87;a:6:{s:1:\"a\";i:88;s:1:\"b\";s:27:\"create:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:88;a:6:{s:1:\"a\";i:89;s:1:\"b\";s:27:\"update:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:89;a:6:{s:1:\"a\";i:90;s:1:\"b\";s:27:\"delete:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:90;a:6:{s:1:\"a\";i:91;s:1:\"b\";s:28:\"restore:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:91;a:6:{s:1:\"a\";i:92;s:1:\"b\";s:32:\"forceDelete:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:92;a:6:{s:1:\"a\";i:93;s:1:\"b\";s:27:\"import:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:93;a:6:{s:1:\"a\";i:94;s:1:\"b\";s:27:\"export:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:94;a:6:{s:1:\"a\";i:95;s:1:\"b\";s:35:\"viewAuditTrail:institution-settings\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:11;s:1:\"r\";a:1:{i:0;i:3;}}i:95;a:6:{s:1:\"a\";i:96;s:1:\"b\";s:31:\"viewAny:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:96;a:6:{s:1:\"a\";i:97;s:1:\"b\";s:28:\"view:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:97;a:6:{s:1:\"a\";i:98;s:1:\"b\";s:30:\"create:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:98;a:6:{s:1:\"a\";i:99;s:1:\"b\";s:30:\"update:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:99;a:6:{s:1:\"a\";i:100;s:1:\"b\";s:30:\"delete:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:100;a:6:{s:1:\"a\";i:101;s:1:\"b\";s:31:\"restore:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:101;a:6:{s:1:\"a\";i:102;s:1:\"b\";s:35:\"forceDelete:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:102;a:6:{s:1:\"a\";i:103;s:1:\"b\";s:30:\"import:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:103;a:6:{s:1:\"a\";i:104;s:1:\"b\";s:30:\"export:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:104;a:6:{s:1:\"a\";i:105;s:1:\"b\";s:38:\"viewAuditTrail:institution-departments\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:7;s:1:\"r\";a:1:{i:0;i:3;}}i:105;a:6:{s:1:\"a\";i:106;s:1:\"b\";s:20:\"viewAny:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:106;a:6:{s:1:\"a\";i:107;s:1:\"b\";s:17:\"view:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:107;a:6:{s:1:\"a\";i:108;s:1:\"b\";s:19:\"create:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:108;a:6:{s:1:\"a\";i:109;s:1:\"b\";s:19:\"update:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:109;a:6:{s:1:\"a\";i:110;s:1:\"b\";s:19:\"delete:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:110;a:6:{s:1:\"a\";i:111;s:1:\"b\";s:20:\"restore:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:111;a:6:{s:1:\"a\";i:112;s:1:\"b\";s:24:\"forceDelete:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:112;a:6:{s:1:\"a\";i:113;s:1:\"b\";s:19:\"import:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:113;a:6:{s:1:\"a\";i:114;s:1:\"b\";s:19:\"export:bank-details\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:114;a:6:{s:1:\"a\";i:115;s:1:\"b\";s:17:\"viewAny:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:115;a:6:{s:1:\"a\";i:116;s:1:\"b\";s:14:\"view:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:116;a:6:{s:1:\"a\";i:117;s:1:\"b\";s:16:\"create:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:117;a:6:{s:1:\"a\";i:118;s:1:\"b\";s:16:\"update:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:118;a:6:{s:1:\"a\";i:119;s:1:\"b\";s:16:\"delete:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:119;a:6:{s:1:\"a\";i:120;s:1:\"b\";s:17:\"restore:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:120;a:6:{s:1:\"a\";i:121;s:1:\"b\";s:21:\"forceDelete:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:121;a:6:{s:1:\"a\";i:122;s:1:\"b\";s:16:\"import:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:122;a:6:{s:1:\"a\";i:123;s:1:\"b\";s:16:\"export:addresses\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:123;a:6:{s:1:\"a\";i:124;s:1:\"b\";s:16:\"viewAny:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:124;a:6:{s:1:\"a\";i:125;s:1:\"b\";s:13:\"view:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:125;a:6:{s:1:\"a\";i:126;s:1:\"b\";s:15:\"create:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:126;a:6:{s:1:\"a\";i:127;s:1:\"b\";s:15:\"update:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:127;a:6:{s:1:\"a\";i:128;s:1:\"b\";s:15:\"delete:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:128;a:6:{s:1:\"a\";i:129;s:1:\"b\";s:16:\"restore:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:129;a:6:{s:1:\"a\";i:130;s:1:\"b\";s:20:\"forceDelete:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:130;a:6:{s:1:\"a\";i:131;s:1:\"b\";s:15:\"import:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}i:131;a:6:{s:1:\"a\";i:132;s:1:\"b\";s:15:\"export:contacts\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";s:1:\"e\";i:12;s:1:\"r\";a:1:{i:0;i:3;}}}s:5:\"roles\";a:1:{i:0;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:19:\"Super Administrator\";s:1:\"c\";N;s:1:\"d\";s:3:\"web\";}}}',1746664896);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `communication_methods` WRITE;
/*!40000 ALTER TABLE `communication_methods` DISABLE KEYS */;
INSERT INTO `communication_methods` VALUES (1,'Email','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL),(2,'Sms','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL),(3,'Phone','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL);
/*!40000 ALTER TABLE `communication_methods` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'Beauty Therapy','Applied Arts','2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(2,'Cosmetology','Applied Arts','2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(3,'Hairdressing','Applied Arts','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(4,'Industrial Clothing Design and Construction Design','Applied Arts','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(5,'Applied Biological Technology','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(6,'Applied Chemical Technology','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(7,'Chemical Engineering','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(8,'Chemical Technology','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(9,'Food Science','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(10,'Horticulture','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(11,'Laboratory Technology','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(12,'Metallurgical Assaying','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(13,'Pharmaceutical Technology','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(14,'Polymer Technology','Applied Science Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(15,'Automobile Electrics And Electronics','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(16,'Automotive Engineering','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(17,'Automotive Precision Machining','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(18,'Diesel Plant Fitting','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(19,'Motor Cycle Machining','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(20,'Motor Vehicle Body Repairs','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(21,'Motor Vehicle Mechanics','Automotive','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(22,'Accountancy','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(23,'Banking and Finance','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(24,'Health Services Management','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(25,'Human Resources Management','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(26,'Pensions & Investments Management','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(27,'Purchasing & Supply Management','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(28,'Sales & Marketing Management','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(29,'Trainers Diploma In Education','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(30,'Transport & Logistics Management','Commerce','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(31,'Architectural Technology','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(32,'Cartography & Geo-Visualization Theory Technology','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(33,'Civil Engineering','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(34,'Quantity Surveying','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(35,'Surveying and Geomatics','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(36,'Urban And Regional Planning','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(37,'Valuation & Estate Management','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(38,'Water Resources & Irrigation Engineering','Civil Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(39,'Building Technology','Construction Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(40,'Carpentry and Joinery','Construction Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(41,'Construction Engineering','Construction Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(42,'Painting and Decorating Technology','Construction Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(43,'Plumbing and Drain Laying','Construction Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(44,'Computer Systems','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(45,'Domestic and Industrial Solar Installation','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(46,'Electrical Power Engineering','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(47,'Electronic Communication Systems','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(48,'Instrumentation and Control Systems','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(49,'Microwave and Radar','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(50,'Mobile and Satellite Communication','Electrical Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(51,'Information Technology','Information Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(52,'Professional Computer Engineering','Information Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(53,'Professional Computing and Information Systems','Information Technology','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(54,'Library and Information Sciences','Library and Information Systems','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(55,'Records Management and Information Sciences','Library and Information Systems','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(56,'Draughting and Design Technology','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(57,'Fabrication Engineering','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(58,'Machine Shop Engineering','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(59,'Mechanical Engineering','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(60,'Millwright Works','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(61,'Plant Engineering','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(62,'Production Engineering','Mechanical and Production Engineering','2025-05-06 22:40:57','2025-05-06 22:40:57',NULL),(63,'Refrigeration and Air Conditioning','Mechanical and Production Engineering','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(64,'Vehicle Body Building','Mechanical and Production Engineering','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(65,'Applied Art and Design','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(66,'Design For Print','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(67,'Fine Arts','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(68,'Machine Printing','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(69,'Multimedia','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(70,'Packaging Machine Minding','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(71,'Photography','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(72,'Printing, Finishing and Converting','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(73,'Print Finishing Technology','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(74,'Print Production Technology','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(75,'Print Origination Technology','Printing and Graphic Arts','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(76,'Broadcast Journalism','Mass Communication','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(77,'Mass Communication','Mass Communication','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(78,'Print Journalism','Mass Communication','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(79,'Public Relations','Mass Communication','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(80,'Office Management','Office Management','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(81,'Bakery Technology and Management','Tourism and Hospitality','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(82,'Culinary Arts','Tourism and Hospitality','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(83,'Professional Cookery','Tourism and Hospitality','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(84,'Tourism and Hospitality Management','Tourism and Hospitality','2025-05-06 22:40:58','2025-05-06 22:40:58',NULL);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Applied Arts',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(2,'Automotive Engineering',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(3,'Business & Management Studies',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(4,'Civil Engineering',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(5,'Construction Engineering',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(6,'Electrical Engineering',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(7,'Information Communication Technology',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(8,'Library & Info Sciences',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(9,'Mass Communication',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(10,'Mechanical & Production Engineering',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(11,'Office Management',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(12,'Printing And Graphics Arts',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(13,'Science Technology',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(14,'Tourism And Hospitality',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` VALUES (1,'Bulawayo',1,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(2,'Chitungwiza',2,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(3,'Harare',2,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(4,'Buhera',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(5,'Chimanimani',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(6,'Chipinge',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(7,'Makoni',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(8,'Mutare',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(9,'Mutasa',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(10,'Nyanga',3,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(11,'Bindura',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(12,'Guruve',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(13,'Mazowe',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(14,'Mbire',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(15,'Mount Darwin',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(16,'Muzarabani',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(17,'Rushinga',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(18,'Shamva',4,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(19,'Chikomba',5,NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(20,'Goromonzi',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(21,'Marondera',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(22,'Mudzi',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(23,'Murehwa',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(24,'Mutoko',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(25,'Seke',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(26,'UMP (Uzumba-Maramba-Pfungwe)',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(27,'Wedza (Hwedza)',5,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(28,'Chegutu',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(29,'Hurungwe',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(30,'Kariba',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(31,'Makonde',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(32,'Mhondoro-Ngezi',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(33,'Sanyati',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(34,'Zvimba',6,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(35,'Bikita',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(36,'Chiredzi',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(37,'Chivi',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(38,'Gutu',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(39,'Masvingo',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(40,'Mwenezi',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(41,'Zaka',7,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(42,'Binga',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(43,'Bubi',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(44,'Hwange',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(45,'Lupane',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(46,'Nkayi',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(47,'Tsholotsho',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(48,'Umguza',8,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(49,'Beitbridge',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(50,'Bulilima',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(51,'Gwanda',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(52,'Insiza',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(53,'Mangwe',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(54,'Matobo',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(55,'Umzingwane',9,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(56,'Chirumhanzu',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(57,'Gokwe North',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(58,'Gokwe South',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(59,'Gweru',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(60,'Kwekwe',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(61,'Mberengwa',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(62,'Shurugwi',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(63,'Zvishavane',10,NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL);
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `divisions`
--

DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

LOCK TABLES `divisions` WRITE;
/*!40000 ALTER TABLE `divisions` DISABLE KEYS */;
INSERT INTO `divisions` VALUES (1,'Business',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(2,'Management',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(3,'Pedagogics',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL);
/*!40000 ALTER TABLE `divisions` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `genders` WRITE;
/*!40000 ALTER TABLE `genders` DISABLE KEYS */;
INSERT INTO `genders` VALUES (1,'Male',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(2,'Female',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL);
/*!40000 ALTER TABLE `genders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
INSERT INTO `grades` VALUES (1,'A',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(2,'B',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(3,'C',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(4,'D',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(5,'E',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(6,'U',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL);
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `institution_departments` WRITE;
/*!40000 ALTER TABLE `institution_departments` DISABLE KEYS */;
INSERT INTO `institution_departments` VALUES (1,1,1,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(2,1,2,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(3,1,3,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(4,1,4,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(5,1,5,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(6,1,6,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(7,1,7,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(8,1,8,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(9,1,9,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(10,1,10,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(11,1,11,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(12,1,12,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(13,1,13,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL),(14,1,14,NULL,'2025-05-06 22:45:06','2025-05-06 22:45:06',NULL);
/*!40000 ALTER TABLE `institution_departments` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,'English',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `levels`
--

DROP TABLE IF EXISTS `levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

LOCK TABLES `levels` WRITE;
/*!40000 ALTER TABLE `levels` DISABLE KEYS */;
INSERT INTO `levels` VALUES (1,'ABMA Level 3',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(2,'ABMA Level 4',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(3,'ABMA Level 5',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(4,'ABMA Level 6',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(5,'NC',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(6,'ND',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(7,'HND',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(8,'BTECH',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(9,'SDP',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL);
/*!40000 ALTER TABLE `levels` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_01_01_000000_create_create_tenants_table',1),(2,'0001_01_01_000000_create_users_table',1),(3,'0001_01_01_000001_create_cache_table',1),(4,'0001_01_01_000002_create_jobs_table',1),(5,'2024_12_10_073103_create_media_table',1),(6,'2024_12_10_073229_create_activity_log_table',1),(7,'2024_12_10_073230_add_event_column_to_activity_log_table',1),(8,'2024_12_10_073231_add_batch_uuid_column_to_activity_log_table',1),(9,'2024_12_10_091905_create_modules_table',1),(10,'2024_12_10_112501_create_permission_tables',1),(11,'2025_01_18_202508_create_communication_methods_table',1),(12,'2025_01_18_222026_create_countries_table',1),(13,'2025_01_19_101127_create_payment_days_table',1),(14,'2025_01_19_101323_create_payment_frequencies_table',1),(15,'2025_01_19_101437_create_payment_methods_table',1),(16,'2025_01_19_125713_create_genders_table',1),(17,'2025_01_19_140446_create_languages_table',1),(18,'2025_01_19_143527_create_provinces_table',1),(19,'2025_01_19_183300_create_statuses_table',1),(20,'2025_01_19_183342_create_races_table',1),(21,'2025_01_19_183404_create_titles_table',1),(22,'2025_03_20_185152_create_addresses_table',1),(23,'2025_03_20_190050_create_contacts_table',1),(24,'2025_03_22_053137_create_address_types_table',1),(25,'2025_04_25_173642_create_departments_table',1),(26,'2025_04_25_173916_create_courses_table',1),(27,'2025_04_25_174007_create_divisions_table',1),(28,'2025_04_25_174046_create_grades_table',1),(29,'2025_04_25_174107_create_levels_table',1),(30,'2025_04_25_174151_create_relationships_table',1),(31,'2025_04_25_174216_create_subjects_table',1),(32,'2025_04_25_193714_create_mode_of_studies_table',1),(33,'2025_04_27_142505_create_districts_table',1),(34,'2025_04_28_135636_create_institution_departments_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `mode_of_studies` WRITE;
/*!40000 ALTER TABLE `mode_of_studies` DISABLE KEYS */;
INSERT INTO `mode_of_studies` VALUES (1,'Full Time',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(2,'Part Time',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(3,'Block Release',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL);
/*!40000 ALTER TABLE `mode_of_studies` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (3,'App\\Models\\Users\\User',1),(3,'App\\Models\\Users\\User',2);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'Accommodations','accommodations',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(2,'Acl','acl',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(3,'Communications','communications',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(4,'Dashboards','dashboards',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(5,'Enrolments','enrolments',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(6,'Examinations','examinations',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(7,'Institution','institution',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(8,'Other','other',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(9,'Reports','reports',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(10,'Root','root',NULL,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(11,'Settings','settings',NULL,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(12,'Shared','shared',NULL,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(13,'Students','students',NULL,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(14,'Tenants','tenants',NULL,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(15,'Users','users',NULL,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL);
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `payment_days` WRITE;
/*!40000 ALTER TABLE `payment_days` DISABLE KEYS */;
INSERT INTO `payment_days` VALUES (1,'1',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(2,'2',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(3,'3',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(4,'4',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(5,'5',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(6,'6',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(7,'7',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(8,'8',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(9,'9',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(10,'10',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(11,'11',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(12,'12',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(13,'13',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(14,'14',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(15,'15',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(16,'16',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(17,'17',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(18,'18',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(19,'19',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(20,'20',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(21,'21',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(22,'22',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(23,'23',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(24,'24',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(25,'25',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(26,'26',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(27,'27',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(28,'28',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(29,'29',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(30,'30',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(31,'31',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL);
/*!40000 ALTER TABLE `payment_days` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `payment_frequencies` WRITE;
/*!40000 ALTER TABLE `payment_frequencies` DISABLE KEYS */;
INSERT INTO `payment_frequencies` VALUES (1,'Monthly',NULL,'2025-05-06 22:40:54','2025-05-06 22:40:54',NULL),(2,'Annually',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(3,'Once off',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL);
/*!40000 ALTER TABLE `payment_frequencies` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Credit Card',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(2,'Cash Payment',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(3,'Debit Order',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(4,'EFT',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(5,'Stop Order',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL);
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view:acl-settings',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(2,'viewAny:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(3,'view:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(4,'create:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(5,'update:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(6,'delete:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(7,'restore:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(8,'forceDelete:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(9,'import:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(10,'export:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(11,'viewAuditTrail:modules',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(12,'viewAny:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(13,'view:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(14,'create:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(15,'update:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(16,'delete:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(17,'restore:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(18,'forceDelete:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(19,'import:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(20,'export:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(21,'viewAuditTrail:roles',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(22,'viewAny:permissions',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(23,'view:permissions',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(24,'create:permissions',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(25,'update:permissions',NULL,'web',2,'2025-05-06 22:40:46','2025-05-06 22:40:46',NULL),(26,'delete:permissions',NULL,'web',2,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(27,'restore:permissions',NULL,'web',2,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(28,'forceDelete:permissions',NULL,'web',2,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(29,'import:permissions',NULL,'web',2,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(30,'export:permissions',NULL,'web',2,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(31,'viewAuditTrail:permissions',NULL,'web',2,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(32,'viewAny:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(33,'view:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(34,'create:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(35,'update:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(36,'delete:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(37,'restore:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(38,'forceDelete:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(39,'import:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(40,'export:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(41,'crud-settings:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(42,'viewAuditTrail:communications',NULL,'web',3,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(43,'viewAny:dashboards',NULL,'web',4,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(44,'view:dashboards',NULL,'web',4,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(45,'viewAny:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(46,'view:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(47,'create:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(48,'update:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(49,'delete:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(50,'restore:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(51,'forceDelete:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(52,'import:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(53,'export:reports',NULL,'web',9,'2025-05-06 22:40:47','2025-05-06 22:40:47',NULL),(54,'viewAny:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(55,'view:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(56,'create:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(57,'update:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(58,'delete:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(59,'restore:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(60,'forceDelete:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(61,'import:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(62,'export:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(63,'crud-settings:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(64,'viewAuditTrail:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(65,'manageOwnData:tenants',NULL,'web',14,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(66,'viewAny:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(67,'view:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(68,'create:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(69,'update:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(70,'delete:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(71,'restore:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(72,'forceDelete:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(73,'import:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(74,'export:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(75,'crud-settings:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(76,'viewAuditTrail:users',NULL,'web',15,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(77,'root:manage',NULL,'web',10,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(78,'view:settings',NULL,'web',11,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(79,'create:settings',NULL,'web',11,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(80,'update:settings',NULL,'web',11,'2025-05-06 22:40:48','2025-05-06 22:40:48',NULL),(81,'delete:settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(82,'restore:settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(83,'forceDelete:settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(84,'import:settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(85,'export:settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(86,'viewAuditTrail:settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(87,'view:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(88,'create:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(89,'update:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(90,'delete:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(91,'restore:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(92,'forceDelete:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(93,'import:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(94,'export:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(95,'viewAuditTrail:institution-settings',NULL,'web',11,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(96,'viewAny:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(97,'view:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(98,'create:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(99,'update:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(100,'delete:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(101,'restore:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(102,'forceDelete:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(103,'import:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(104,'export:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(105,'viewAuditTrail:institution-departments',NULL,'web',7,'2025-05-06 22:40:49','2025-05-06 22:40:49',NULL),(106,'viewAny:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(107,'view:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(108,'create:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(109,'update:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(110,'delete:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(111,'restore:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(112,'forceDelete:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(113,'import:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(114,'export:bank-details',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(115,'viewAny:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(116,'view:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(117,'create:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(118,'update:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(119,'delete:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(120,'restore:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(121,'forceDelete:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(122,'import:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(123,'export:addresses',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(124,'viewAny:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(125,'view:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(126,'create:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(127,'update:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(128,'delete:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(129,'restore:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(130,'forceDelete:contacts',NULL,'web',12,'2025-05-06 22:40:50','2025-05-06 22:40:50',NULL),(131,'import:contacts',NULL,'web',12,'2025-05-06 22:40:51','2025-05-06 22:40:51',NULL),(132,'export:contacts',NULL,'web',12,'2025-05-06 22:40:51','2025-05-06 22:40:51',NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `provinces` WRITE;
/*!40000 ALTER TABLE `provinces` DISABLE KEYS */;
INSERT INTO `provinces` VALUES (1,'Bulawayo',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(2,'Harare',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(3,'Manicaland',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(4,'Mashonaland Central',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(5,'Mashonaland East',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(6,'Mashonaland West',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(7,'Masvingo',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(8,'Matebeleland North',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(9,'Matebeleland South',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(10,'Midlands',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL),(11,'Unknown Province',NULL,'2025-05-06 22:40:55','2025-05-06 22:40:55',NULL);
/*!40000 ALTER TABLE `provinces` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `races` WRITE;
/*!40000 ALTER TABLE `races` DISABLE KEYS */;
INSERT INTO `races` VALUES (1,'African',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(2,'Black',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(3,'White',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(4,'Colored',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(5,'Indian',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL);
/*!40000 ALTER TABLE `races` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `relationships` WRITE;
/*!40000 ALTER TABLE `relationships` DISABLE KEYS */;
INSERT INTO `relationships` VALUES (1,'Parent',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(2,'Spouse',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(3,'Guardian',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL);
/*!40000 ALTER TABLE `relationships` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,3),(2,3),(3,3),(4,3),(5,3),(6,3),(7,3),(8,3),(9,3),(10,3),(11,3),(12,3),(13,3),(14,3),(15,3),(16,3),(17,3),(18,3),(19,3),(20,3),(21,3),(22,3),(23,3),(24,3),(25,3),(26,3),(27,3),(28,3),(29,3),(30,3),(31,3),(32,3),(33,3),(34,3),(35,3),(36,3),(37,3),(38,3),(39,3),(40,3),(41,3),(42,3),(43,3),(44,3),(45,3),(46,3),(47,3),(48,3),(49,3),(50,3),(51,3),(52,3),(53,3),(54,3),(55,3),(56,3),(57,3),(58,3),(59,3),(60,3),(61,3),(62,3),(63,3),(64,3),(66,3),(67,3),(68,3),(69,3),(70,3),(71,3),(72,3),(73,3),(74,3),(75,3),(76,3),(77,3),(78,3),(79,3),(80,3),(81,3),(82,3),(83,3),(84,3),(85,3),(86,3),(87,3),(88,3),(89,3),(90,3),(91,3),(92,3),(93,3),(94,3),(95,3),(96,3),(97,3),(98,3),(99,3),(100,3),(101,3),(102,3),(103,3),(104,3),(105,3),(106,3),(107,3),(108,3),(109,3),(110,3),(111,3),(112,3),(113,3),(114,3),(115,3),(116,3),(117,3),(118,3),(119,3),(120,3),(121,3),(122,3),(123,3),(124,3),(125,3),(126,3),(127,3),(128,3),(129,3),(130,3),(131,3),(132,3);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Registrar',NULL,'web','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL),(2,'Selection officer',NULL,'web','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL),(3,'Super Administrator',NULL,'web','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('FHrpRyqddOYGMkK5MP6QVllDrUt0gM5H8LmdXOR0',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM1ZiZ0VtOXJZRjBsNXNvYVg2Mmw1UUNxaGN0UHo5MjhObEhTWDNLQSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1746588677),('IveRBbP2aOa1MzCYe1WhnmO1zMI8mo7NoZ46mXbR',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiaUZnTnZSTGhaUE9SZzZPRU14TFcwS0k4bEtKcklIaGhMc1R4T05pcyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjUzOiJodHRwczovL2hyZXBvbHkudGVzdC9hcGkvdjEvZGVwYXJ0bWVudHM/cGFnZV9zaXplPTEwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiRSR0lLY0o2NGFDQkpqREtKa3lwdEl1NDNNRjVQOVB4YkhMSDRqWnlVLjdMVVlicFVlRGdjTyI7fQ==',1746578906);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `statuses` WRITE;
/*!40000 ALTER TABLE `statuses` DISABLE KEYS */;
INSERT INTO `statuses` VALUES (1,'Active',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(2,'Waiting Approval',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(3,'Inactive',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL);
/*!40000 ALTER TABLE `statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Accounts',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(2,'Agriculture',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(3,'Art',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(4,'Bible Knowledge',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(5,'Building Studies',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(6,'Business and Enterprise Skills',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(7,'Business Studies',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(8,'Chinese',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(9,'Commerce',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(10,'Computer Science',NULL,'2025-05-06 22:40:58','2025-05-06 22:40:58',NULL),(11,'Design and Technology',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(12,'Economics',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(13,'English',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(14,'Fashion and Fabrics',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(15,'Food and Nutrition',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(16,'French',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(17,'Geography',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(18,'German',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(19,'History',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(20,'Integrated Science',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(21,'Literature in English',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(22,'Mathematics',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(23,'Metal Technology and Design',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(24,'Music',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(25,'Ndebele',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(26,'Physical Education, Sport and Mass Displays',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(27,'Religious Studies',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(28,'Shona',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(29,'Spanish',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(30,'Technical Graphics',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL),(31,'Wood Technology and Design',NULL,'2025-05-06 22:40:59','2025-05-06 22:40:59',NULL);
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'Harare Poly',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL),(2,'Penstej Systems',NULL,'2025-05-06 22:40:45','2025-05-06 22:40:45',NULL);
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `titles` WRITE;
/*!40000 ALTER TABLE `titles` DISABLE KEYS */;
INSERT INTO `titles` VALUES (1,'Mr',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(2,'Mrs',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(3,'Miss',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(4,'Dr',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL),(5,'Prof',NULL,'2025-05-06 22:40:56','2025-05-06 22:40:56',NULL);
/*!40000 ALTER TABLE `titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_tenant_id_index` (`tenant_id`),
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Super User','su@penstejsystems.com',NULL,'$2y$12$RGIKcJ64aCBJjDKJkyptIu43MF5P9PxbHLH4jZyU.7LUYbpUeDgcO',NULL,'3iB2zMbn1OeLu2y3PzFxaSoYJi4wMNuewi3VzD1S4yNSIE9ndCzZXhUUvDVX','2025-05-06 22:40:51','2025-05-06 22:40:51',NULL),(2,1,'Software Developer','developer@penstejsystems.com',NULL,'$2y$12$/dmbx0oRQcO5Ugr3CHAsAeeexZ3YLdehos3NDGMg3LcH/AS1v0Pfu',NULL,NULL,'2025-05-06 22:40:51','2025-05-06 22:40:51',NULL);
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

-- Dump completed on 2025-05-07  6:24:25
