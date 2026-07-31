/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `accessories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accessories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `accessory_name` varchar(255) NOT NULL,
  `jantina` int NOT NULL,
  `pangkat` int NOT NULL,
  `religion` varchar(100) NOT NULL,
  `sizes` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int NOT NULL,
  `auth_code` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assigned_uniforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assigned_uniforms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ketukangans_id` varchar(255) NOT NULL,
  `pangkats_id` text,
  `uniforms_id` text NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gen_user_id` int unsigned NOT NULL,
  `uniforms_id` int unsigned NOT NULL,
  `clothes_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clothes_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` text COLLATE utf8mb4_unicode_ci,
  `quantity` smallint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_items_user_uniform_cloth_unique` (`gen_user_id`,`uniforms_id`,`clothes_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gen_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gen_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `s_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `activation_status` int NOT NULL DEFAULT '0',
  `profile_status` int NOT NULL DEFAULT '0',
  `auth_code` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jantinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jantinas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ketukangans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ketukangans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `officer_recruit` int NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mobile_api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_api_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gen_user_id` int unsigned NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_api_tokens_token_hash_unique` (`token_hash`),
  KEY `mobile_api_tokens_gen_user_id_index` (`gen_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ordered_clothes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordered_clothes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` varchar(255) NOT NULL,
  `clothes` varchar(255) NOT NULL,
  `clothes_slug` varchar(255) NOT NULL,
  `size` varchar(255) NOT NULL,
  `quantity` smallint unsigned NOT NULL DEFAULT '1',
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `clothes_slug` (`clothes_slug`),
  KEY `size` (`size`),
  KEY `clothes` (`clothes`),
  KEY `ordered_clothes_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `uniforms_id` varchar(255) NOT NULL,
  `status` varchar(1) NOT NULL DEFAULT '1',
  `remarks` text,
  `collection_date` date DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  `deleted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `deleted` (`deleted`),
  KEY `user_id` (`user_id`),
  KEY `uniforms_id` (`uniforms_id`),
  KEY `orders_deleted_created_id_index` (`deleted`,`created_at`,`id`),
  KEY `orders_user_deleted_index` (`user_id`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pangkats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pangkats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `officer_recruit` int DEFAULT NULL,
  `piliih_angkatan_id` varchar(255) DEFAULT NULL,
  `pangkats_order` int DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pangkats_order` (`pangkats_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `s_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `piliih_angkatan` int NOT NULL,
  `pangkat` int NOT NULL,
  `ketukangan_type` int NOT NULL,
  `ketukangan` int NOT NULL,
  `unit` int NOT NULL,
  `jantina` int NOT NULL,
  `telephone_number` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `nama_waris` varchar(255) NOT NULL,
  `telephone_number_waris` varchar(255) NOT NULL,
  `status_penggunaan` int NOT NULL,
  `name_tag` varchar(255) NOT NULL,
  `unit_lama` varchar(255) NOT NULL,
  `kem_lama` varchar(255) NOT NULL,
  `spl_lama` varchar(255) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `unit` (`unit`),
  KEY `ketukangan` (`ketukangan`),
  KEY `ketukangan_type` (`ketukangan_type`),
  KEY `pangkat` (`pangkat`),
  KEY `personal_details_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `piliih_angkatans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `piliih_angkatans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_settings_setting_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_penggunaans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_penggunaans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uniform_clothes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uniform_clothes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniforms_id` varchar(255) NOT NULL,
  `clothes_type` text NOT NULL,
  `clothes_slug` text NOT NULL,
  `parent_uniform_id` int DEFAULT NULL,
  `clothes_size` varchar(255) DEFAULT NULL,
  `jantina` varchar(300) DEFAULT NULL,
  `pangkat` varchar(300) DEFAULT NULL,
  `ketukangan` varchar(300) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `accessory` int NOT NULL DEFAULT '0',
  `clothes_photo` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uniform_rank_hidden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uniform_rank_hidden` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pangkat_id` int unsigned NOT NULL,
  `uniforms_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniform_rank_hidden_unique` (`pangkat_id`,`uniforms_id`),
  KEY `uniform_rank_hidden_pangkat_id_index` (`pangkat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uniform_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uniform_scales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pangkat_id` int unsigned NOT NULL,
  `uniform_clothes_id` int unsigned NOT NULL,
  `max_quantity` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniform_scales_rank_item_unique` (`pangkat_id`,`uniform_clothes_id`),
  KEY `uniform_scales_pangkat_id_index` (`pangkat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uniforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uniforms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniform_type` varchar(3) NOT NULL,
  `uniform_name` varchar(255) DEFAULT NULL,
  `uniform_photo` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2026_07_04_000002_add_uniform_order_list_indexes',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_07_04_000001_add_order_status_fields',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_07_05_000003_update_order_status_to_codes',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_07_10_000001_create_mobile_api_tokens_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_07_10_000002_create_cart_items_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_07_05_000004_add_photo_to_uniform_clothes_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_07_19_000005_normalize_uploaded_image_paths',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_07_22_000001_create_uniform_scales_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_07_22_000002_add_quantity_to_cart_and_ordered_clothes',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_07_25_000001_create_uniform_rank_hidden_table',11);
