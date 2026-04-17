-- MySQL dump 10.13  Distrib 8.0.35, for FreeBSD13.0 (amd64)
--
-- Host: mysql80.qomolangma2.sakura.ne.jp    Database: qomolangma2_matsu
-- ------------------------------------------------------
-- Server version	8.0.40

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
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('attending','absent','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '出欠ステータス',
  `guests_count` int NOT NULL DEFAULT '0' COMMENT '同伴者数',
  `remarks` text COLLATE utf8mb4_unicode_ci COMMENT '備考・メッセージ',
  `responded_at` datetime DEFAULT NULL COMMENT '回答日時',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_event_id_user_id_unique` (`event_id`,`user_id`),
  KEY `attendances_user_id_foreign` (`user_id`),
  KEY `attendances_status_index` (`status`),
  KEY `attendances_responded_at_index` (`responded_at`),
  CONSTRAINT `attendances_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'カテゴリー名（例: 東京地区会）',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL用スラッグ（例: tokyo）',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '説明',
  `type` enum('district','role','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'district' COMMENT 'タイプ（地区会、役職、その他）',
  `display_order` int NOT NULL DEFAULT '0' COMMENT '表示順',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効/無効',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_name_index` (`name`),
  KEY `categories_slug_index` (`slug`),
  KEY `categories_type_index` (`type`),
  KEY `categories_is_active_index` (`is_active`),
  KEY `categories_display_order_index` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_user`
--

DROP TABLE IF EXISTS `category_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL COMMENT '割り当て日時',
  `assigned_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'メモ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_user_user_id_category_id_unique` (`user_id`,`category_id`),
  KEY `category_user_assigned_by_foreign` (`assigned_by`),
  KEY `category_user_user_id_index` (`user_id`),
  KEY `category_user_category_id_index` (`category_id`),
  CONSTRAINT `category_user_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `category_user_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_user`
--

LOCK TABLES `category_user` WRITE;
/*!40000 ALTER TABLE `category_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'イベント名',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '説明',
  `event_date` datetime NOT NULL COMMENT 'イベント日時',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '場所',
  `graduation_year` year DEFAULT NULL COMMENT '対象卒業年度（nullの場合は全体向け）',
  `capacity` int DEFAULT NULL COMMENT '定員',
  `deadline` datetime DEFAULT NULL COMMENT '申込締切',
  `created_by` bigint unsigned NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1' COMMENT '公開フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_created_by_foreign` (`created_by`),
  KEY `events_event_date_index` (`event_date`),
  KEY `events_graduation_year_index` (`graduation_year`),
  KEY `events_deadline_index` (`deadline`),
  KEY `events_is_published_index` (`is_published`),
  CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_01_01_000001_create_users_table',1),(2,'2024_01_01_000002_create_reference_rosters_table',1),(3,'2024_01_01_000003_create_events_table',1),(4,'2024_01_01_000004_create_news_table',1),(5,'2024_01_01_000005_create_attendances_table',1),(6,'2024_01_01_000006_add_approval_columns_to_users_table',1),(7,'2026_02_18_095958_add_phone_to_users_table',1),(8,'2026_02_18_102133_create_categories_table',1),(9,'2026_02_18_102143_create_category_user_table',1),(10,'2026_02_18_153451_create_cache_table',2),(11,'2026_02_18_220000_update_news_table_for_multiple_years',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'タイトル',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '本文',
  `target_graduation_years` json DEFAULT NULL COMMENT '対象卒業年度（JSON配列、nullの場合は全学年）',
  `is_line_notification` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'LINE通知フラグ',
  `is_top_display` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'TOP掲載フラグ',
  `published_at` datetime DEFAULT NULL COMMENT '公開日時',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `news_is_top_display_index` (`is_top_display`),
  KEY `news_published_at_index` (`published_at`),
  KEY `news_is_top_display_display_order_index` (`is_top_display`),
  KEY `news_created_by_foreign` (`created_by`),
  CONSTRAINT `news_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference_rosters`
--

DROP TABLE IF EXISTS `reference_rosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reference_rosters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `graduation_term` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卒業回',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '氏名',
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '性別',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '状態/会員区分',
  `role_1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '役職1',
  `role_2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '役職2',
  `former_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '旧姓',
  `kana` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'フリガナ',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '備考/更新履歴',
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '郵便番号',
  `address_1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '住所1',
  `address_2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '住所2',
  `address_3` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '住所3',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '電話番号',
  `is_registered` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'システム登録済みフラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_graduation_term` (`graduation_term`),
  KEY `idx_name` (`name`),
  KEY `idx_kana` (`kana`),
  KEY `idx_term_name` (`graduation_term`,`name`),
  KEY `idx_term_kana` (`graduation_term`,`kana`),
  KEY `idx_is_registered` (`is_registered`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference_rosters`
--

LOCK TABLES `reference_rosters` WRITE;
/*!40000 ALTER TABLE `reference_rosters` DISABLE KEYS */;
/*!40000 ALTER TABLE `reference_rosters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `line_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'LINE ID（一意）',
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '姓',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名',
  `last_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '姓（カナ）',
  `first_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '名（カナ）',
  `birth_date` date DEFAULT NULL COMMENT '生年月日',
  `graduation_year` year NOT NULL COMMENT '卒業年度',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メールアドレス',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '電話番号',
  `postal_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '郵便番号',
  `address` text COLLATE utf8mb4_unicode_ci COMMENT '住所',
  `mail_unreachable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '郵送物不達フラグ',
  `role` enum('general','year_admin','master_admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general' COMMENT '権限（一般/学年管理者/マスター管理者）',
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '承認ステータス',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT '承認日時',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approval_note` text COLLATE utf8mb4_unicode_ci COMMENT '承認メモ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_line_id_unique` (`line_id`),
  KEY `users_graduation_year_index` (`graduation_year`),
  KEY `users_role_index` (`role`),
  KEY `users_last_name_first_name_index` (`last_name`,`first_name`),
  KEY `users_approved_by_foreign` (`approved_by`),
  KEY `users_approval_status_index` (`approval_status`),
  CONSTRAINT `users_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'master_admin_line_001','マスター','管理者','マスター','カンリシャ','1980-01-01',1998,'master@matsu.localhost',NULL,NULL,NULL,0,'master_admin','approved','2026-02-17 23:48:52',NULL,NULL,'2026-02-17 23:48:52','2026-02-17 23:48:52',NULL,NULL,NULL);
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

-- Dump completed on 2026-04-07 15:54:20
