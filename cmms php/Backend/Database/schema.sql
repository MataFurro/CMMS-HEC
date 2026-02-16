-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: biocmms
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

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
-- Table structure for table `asset_recalls`
--

DROP TABLE IF EXISTS `asset_recalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_recalls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK central al equipo',
  `recall_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agency` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISP, FDA, etc.',
  `priority` enum('Baja','Media','Alta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Media',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recall_asset` (`asset_id`),
  CONSTRAINT `asset_recalls_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_recalls`
--

LOCK TABLES `asset_recalls` WRITE;
/*!40000 ALTER TABLE `asset_recalls` DISABLE KEYS */;
INSERT INTO `asset_recalls` VALUES (1,'AL-500-00441','AV-2024-01','ISP','Alta','Falla en software de goteo',0,'2026-02-13 16:15:34');
/*!40000 ALTER TABLE `asset_recalls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: PB-840-00122',
  `serial_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ownership` enum('Propio','Comodato','Arriendo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Propio',
  `criticality` enum('CRITICAL','RELEVANT','LOW') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RELEVANT',
  `status` enum('OPERATIVE','MAINTENANCE','NO_OPERATIVE','OPERATIVE_WITH_OBS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPERATIVE',
  `riesgo_ge` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Life Support, High Risk, etc.',
  `codigo_umdns` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `purchased_year` year DEFAULT NULL,
  `acquisition_cost` decimal(12,2) DEFAULT '0.00',
  `total_useful_life` int DEFAULT NULL COMMENT 'Años de vida útil total',
  `useful_life_pct` int DEFAULT NULL COMMENT 'Porcentaje de vida útil restante',
  `years_remaining` int DEFAULT NULL,
  `warranty_expiration` date DEFAULT NULL,
  `under_maintenance_plan` tinyint(1) NOT NULL DEFAULT '0',
  `en_uso` tinyint(1) NOT NULL DEFAULT '1',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `funcion_ge` int DEFAULT '0' COMMENT 'Puntaje función (0-10)',
  `riesgo_ge_score` int DEFAULT '0' COMMENT 'Puntaje riesgo (0-5)',
  `mantenimiento_ge` int DEFAULT '0' COMMENT 'Puntaje mantenimiento (0-5)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assets_status` (`status`),
  KEY `idx_assets_criticality` (`criticality`),
  KEY `idx_assets_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES ('AL-500-00441','SN-882211-X','Bomba de Infusión','Alaris','GH Plus','Urgencias - Sala 01','Box 1','Becton Dickinson','Comodato','RELEVANT','MAINTENANCE','High Risk','13-215','2021-02-15',2021,8500.00,10,85,7,'2026-02-15',1,0,NULL,NULL,8,4,3,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('DF-CU-00210','SN-554433-Z','Desfibrilador','Zoll','R Series','Piso 3 - Torre A','Carro de Paro','Medtronic','Propio','CRITICAL','NO_OPERATIVE','Life Support','11-129','2020-03-30',2020,15000.00,10,40,4,'2025-03-30',1,0,NULL,NULL,10,5,3,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('ECG-2024-001','SN-ECG-9988','Electrocardiógrafo','Philips','PageWriter TC70','Cardiología','Consulta 2','Philips Medical','Propio','RELEVANT','OPERATIVE_WITH_OBS',NULL,NULL,NULL,2023,5500.00,8,60,4,'2027-01-01',1,1,NULL,NULL,5,2,3,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('MM-X3-00922','SN-773344-Y','Monitor Multiparamétrico','Mindray','BeneVision X3','Pabellón 03','Mesa Anestesia','Mindray Chile','Propio','CRITICAL','OPERATIVE_WITH_OBS','High Risk','12-630','2022-08-20',2022,12000.00,10,90,8,'2025-08-20',1,1,NULL,NULL,9,4,3,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('PB-840-00122','SN-992031-B','Ventilador Mecánico','Puritan Bennett','840','UCI Adultos - Box 04','Cama 4','Draeger Medical','Propio','CRITICAL','OPERATIVE','Life Support','17-429','2020-05-10',2020,45000.00,10,75,4,'2026-12-15',1,1,NULL,NULL,10,5,4,'2026-02-13 16:15:34','2026-02-13 16:15:34');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CREATE, UPDATE, DELETE, SIGN, LOGIN, etc.',
  `asset_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FK central al equipo (si aplica)',
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'asset, work_order, checklist, etc.',
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_at_user` (`user_id`),
  KEY `idx_at_asset` (`asset_id`),
  KEY `idx_at_timestamp` (`timestamp`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `audit_trail_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_trail`
--

LOCK TABLES `audit_trail` WRITE;
/*!40000 ALTER TABLE `audit_trail` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_trail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_results`
--

DROP TABLE IF EXISTS `checklist_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `work_order_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK central al equipo',
  `template_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qualitative_results` json DEFAULT NULL,
  `quantitative_results` json DEFAULT NULL,
  `electrical_safety_results` json DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `completed_by` (`completed_by`),
  KEY `idx_cr_asset` (`asset_id`),
  KEY `idx_cr_wo` (`work_order_id`),
  CONSTRAINT `checklist_results_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `checklist_results_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `checklist_results_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_results`
--

LOCK TABLES `checklist_results` WRITE;
/*!40000 ALTER TABLE `checklist_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklist_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messenger_reports`
--

DROP TABLE IF EXISTS `messenger_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servicio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `texto` text COLLATE utf8mb4_unicode_ci,
  `imagen_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_reports`
--

LOCK TABLES `messenger_reports` WRITE;
/*!40000 ALTER TABLE `messenger_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ot_attachments`
--

DROP TABLE IF EXISTS `ot_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ot_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `work_order_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK central al equipo',
  `uploaded_by` int DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ruta en uploads/ot/{work_order_id}/',
  `file_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image/jpeg',
  `caption` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` enum('antes','durante','despues','evidencia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'evidencia',
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_ota_asset` (`asset_id`),
  KEY `idx_ota_wo` (`work_order_id`),
  CONSTRAINT `ot_attachments_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ot_attachments_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ot_attachments_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ot_attachments`
--

LOCK TABLES `ot_attachments` WRITE;
/*!40000 ALTER TABLE `ot_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ot_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_requests`
--

DROP TABLE IF EXISTS `service_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_requests` (
  `id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: SOL-2026-0045',
  `asset_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK central al equipo',
  `requested_by` int NOT NULL,
  `priority` enum('Baja','Media','Alta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Media',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pendiente','Revisada','Convertida_OT','Rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `generated_ot_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'OT generada si fue aprobada',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requested_by` (`requested_by`),
  KEY `generated_ot_id` (`generated_ot_id`),
  KEY `idx_sr_asset` (`asset_id`),
  KEY `idx_sr_status` (`status`),
  CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`generated_ot_id`) REFERENCES `work_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_requests`
--

LOCK TABLES `service_requests` WRITE;
/*!40000 ALTER TABLE `service_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technicians`
--

DROP TABLE IF EXISTS `technicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `technicians` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `specialty` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_ots` int NOT NULL DEFAULT '0',
  `completed_ots` int NOT NULL DEFAULT '0',
  `capacity_pct` int NOT NULL DEFAULT '0' COMMENT '0-100%',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `technicians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technicians`
--

LOCK TABLES `technicians` WRITE;
/*!40000 ALTER TABLE `technicians` DISABLE KEYS */;
INSERT INTO `technicians` VALUES (1,2,'Ing. Clínico Sr.',8,12,85,'2026-02-13 16:15:34'),(2,3,'Técnico Biomédico',3,15,45,'2026-02-13 16:15:34'),(3,4,'Técnico Especialista',5,10,60,'2026-02-13 16:15:34');
/*!40000 ALTER TABLE `technicians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Auditor','Tecnico','Ingeniero','Admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tecnico',
  `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Lic. Auditor','auditor@biocmms.com','$2y$10$placeholder_hash','Auditor','https://i.pravatar.cc/150?u=auditor',1,'2026-02-13 16:15:34','2026-02-13 16:15:34'),(2,'Ing. Roberto Jefe','jefe@biocmms.com','$2y$10$placeholder_hash','Ingeniero','https://i.pravatar.cc/150?u=chief',1,'2026-02-13 16:15:34','2026-02-13 16:15:34'),(3,'Ing. Laura','ing@biocmms.com','$2y$10$placeholder_hash','Ingeniero','https://i.pravatar.cc/150?u=eng',1,'2026-02-13 16:15:34','2026-02-13 16:15:34'),(4,'Téc. Mario','tec@biocmms.com','$2y$10$placeholder_hash','Tecnico','https://i.pravatar.cc/150?u=tech',1,'2026-02-13 16:15:34','2026-02-13 16:15:34');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_orders` (
  `id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: OT-2026-4584',
  `asset_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK central al equipo',
  `type` enum('Preventiva','Correctiva','Calibracion') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pendiente','En Proceso','Terminada','Cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `assigned_tech_id` int DEFAULT NULL,
  `created_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `priority` enum('Baja','Media','Alta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Media',
  `checklist_template` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Key del template en checklist_templates.php',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `duration_hours` decimal(6,2) DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wo_asset` (`asset_id`),
  KEY `idx_wo_status` (`status`),
  KEY `idx_wo_type` (`type`),
  KEY `idx_wo_tech` (`assigned_tech_id`),
  CONSTRAINT `work_orders_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `work_orders_ibfk_2` FOREIGN KEY (`assigned_tech_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
INSERT INTO `work_orders` VALUES ('OT-2024-1105','PB-840-00122','Preventiva','Terminada',4,'2024-08-15',NULL,'Alta','ventilador_mecanico',NULL,0.00,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('OT-2025-3210','DF-CU-00210','Preventiva','Terminada',3,'2025-11-20',NULL,'Baja',NULL,NULL,0.00,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('OT-2026-4583','AL-500-00441','Correctiva','En Proceso',4,'2026-02-10',NULL,'Media',NULL,NULL,0.00,'2026-02-13 16:15:34','2026-02-13 16:15:34'),('OT-2026-4584','MM-X3-00922','Calibracion','Pendiente',3,'2026-02-11',NULL,'Baja',NULL,NULL,0.00,'2026-02-13 16:15:34','2026-02-13 16:15:34');
/*!40000 ALTER TABLE `work_orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-13 22:05:05
