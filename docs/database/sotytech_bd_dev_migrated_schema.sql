/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.12-MariaDB, for osx10.20 (arm64)
--
-- Host: 127.0.0.1    Database: sotytech_bd_dev
-- ------------------------------------------------------
-- Server version	11.4.12-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `agente_inmueble`
--

DROP TABLE IF EXISTS `agente_inmueble`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agente_inmueble` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agente_id` bigint(20) unsigned NOT NULL,
  `inmueble_id` bigint(20) unsigned NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agente_inmueble` (`agente_id`,`inmueble_id`),
  KEY `idx_agente_inmueble_agente` (`agente_id`),
  KEY `idx_agente_inmueble_inmueble` (`inmueble_id`),
  KEY `idx_agente_inmueble_principal` (`inmueble_id`,`es_principal`),
  CONSTRAINT `fk_agente_inmueble_agente` FOREIGN KEY (`agente_id`) REFERENCES `agentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agente_inmueble_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_agente_inmueble_principal` CHECK (`es_principal` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentes`
--

DROP TABLE IF EXISTS `agentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agentes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `numero_empleado` varchar(30) NOT NULL,
  `telefono_corporativo` varchar(20) DEFAULT NULL,
  `zona_asignacion` varchar(100) DEFAULT NULL,
  `horario` text DEFAULT NULL,
  `porcentaje_comision` decimal(5,2) NOT NULL DEFAULT 0.00,
  `foto_path` varchar(500) DEFAULT NULL,
  `estado_laboral` varchar(20) NOT NULL DEFAULT 'activo',
  `fecha_contratacion` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agentes_user` (`user_id`),
  UNIQUE KEY `uq_agentes_numero_empleado` (`numero_empleado`),
  KEY `idx_agentes_estado` (`estado_laboral`),
  KEY `idx_agentes_zona` (`zona_asignacion`),
  KEY `idx_agentes_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_agentes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_agentes_estado` CHECK (`estado_laboral` in ('activo','inactivo')),
  CONSTRAINT `chk_agentes_comision` CHECK (`porcentaje_comision` >= 0 and `porcentaje_comision` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `entidad` varchar(80) DEFAULT NULL,
  `entidad_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_hash` char(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `fecha_evento` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bitacora_usuario` (`user_id`),
  KEY `idx_bitacora_accion` (`accion`),
  KEY `idx_bitacora_entidad` (`entidad`,`entidad_id`),
  KEY `idx_bitacora_fecha` (`fecha_evento`),
  CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`),
  KEY `idx_categorias_activo` (`activo`),
  KEY `idx_categorias_deleted_at` (`deleted_at`),
  CONSTRAINT `chk_categorias_activo` CHECK (`activo` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorias_documentos`
--

DROP TABLE IF EXISTS `categorias_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_documentos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_documentos_nombre` (`nombre`),
  KEY `idx_categorias_documentos_activo` (`activo`),
  KEY `idx_categorias_documentos_deleted_at` (`deleted_at`),
  CONSTRAINT `chk_categorias_documentos_activo` CHECK (`activo` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cita_historial`
--

DROP TABLE IF EXISTS `cita_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cita_historial` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cita_id` bigint(20) unsigned NOT NULL,
  `modificado_por_user_id` bigint(20) unsigned NOT NULL,
  `agente_anterior_id` bigint(20) unsigned NOT NULL,
  `agente_nuevo_id` bigint(20) unsigned NOT NULL,
  `fecha_inicio_anterior` datetime NOT NULL,
  `fecha_fin_anterior` datetime NOT NULL,
  `fecha_inicio_nueva` datetime NOT NULL,
  `fecha_fin_nueva` datetime NOT NULL,
  `motivo` varchar(500) NOT NULL,
  `tipo_cambio` varchar(30) NOT NULL DEFAULT 'reprogramacion',
  `fecha_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cita_historial_cita` (`cita_id`),
  KEY `idx_cita_historial_usuario` (`modificado_por_user_id`),
  KEY `idx_cita_historial_agente_anterior` (`agente_anterior_id`),
  KEY `idx_cita_historial_agente_nuevo` (`agente_nuevo_id`),
  KEY `idx_cita_historial_fecha` (`fecha_modificacion`),
  CONSTRAINT `fk_cita_historial_agente_anterior` FOREIGN KEY (`agente_anterior_id`) REFERENCES `agentes` (`id`),
  CONSTRAINT `fk_cita_historial_agente_nuevo` FOREIGN KEY (`agente_nuevo_id`) REFERENCES `agentes` (`id`),
  CONSTRAINT `fk_cita_historial_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  CONSTRAINT `fk_cita_historial_usuario` FOREIGN KEY (`modificado_por_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_cita_historial_tipo` CHECK (`tipo_cambio` in ('reprogramacion','cambio_agente','reprogramacion_y_agente')),
  CONSTRAINT `chk_cita_historial_fecha_anterior` CHECK (`fecha_fin_anterior` > `fecha_inicio_anterior`),
  CONSTRAINT `chk_cita_historial_fecha_nueva` CHECK (`fecha_fin_nueva` > `fecha_inicio_nueva`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `citas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `agente_id` bigint(20) unsigned NOT NULL,
  `inmueble_id` bigint(20) unsigned NOT NULL,
  `oportunidad_id` bigint(20) unsigned DEFAULT NULL,
  `creado_por_user_id` bigint(20) unsigned NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'programada',
  `motivo` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_citas_cliente` (`cliente_id`),
  KEY `idx_citas_agente` (`agente_id`),
  KEY `idx_citas_inmueble` (`inmueble_id`),
  KEY `idx_citas_oportunidad` (`oportunidad_id`),
  KEY `idx_citas_creador` (`creado_por_user_id`),
  KEY `idx_citas_agente_horario` (`agente_id`,`fecha_inicio`,`fecha_fin`),
  KEY `idx_citas_inmueble_horario` (`inmueble_id`,`fecha_inicio`,`fecha_fin`),
  KEY `idx_citas_estado` (`estado`),
  KEY `idx_citas_fecha_inicio` (`fecha_inicio`),
  KEY `idx_citas_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_citas_agente` FOREIGN KEY (`agente_id`) REFERENCES `agentes` (`id`),
  CONSTRAINT `fk_citas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_citas_creador` FOREIGN KEY (`creado_por_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_citas_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`),
  CONSTRAINT `fk_citas_oportunidad` FOREIGN KEY (`oportunidad_id`) REFERENCES `oportunidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_citas_fechas` CHECK (`fecha_fin` > `fecha_inicio`),
  CONSTRAINT `chk_citas_estado` CHECK (`estado` in ('programada','confirmada','completada','cancelada','no_asistio'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cliente_agente`
--

DROP TABLE IF EXISTS `cliente_agente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente_agente` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `agente_id` bigint(20) unsigned NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_agente` (`cliente_id`,`agente_id`),
  KEY `idx_cliente_agente_cliente` (`cliente_id`),
  KEY `idx_cliente_agente_agente` (`agente_id`),
  KEY `idx_cliente_agente_principal` (`cliente_id`,`es_principal`),
  CONSTRAINT `fk_cliente_agente_agente` FOREIGN KEY (`agente_id`) REFERENCES `agentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cliente_agente_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_cliente_agente_principal` CHECK (`es_principal` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cliente_inmueble_intereses`
--

DROP TABLE IF EXISTS `cliente_inmueble_intereses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente_inmueble_intereses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `inmueble_id` bigint(20) unsigned NOT NULL,
  `nivel_interes` varchar(20) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'activo',
  `notas` text DEFAULT NULL,
  `fecha_interes` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_inmueble` (`cliente_id`,`inmueble_id`),
  KEY `idx_intereses_cliente` (`cliente_id`),
  KEY `idx_intereses_inmueble` (`inmueble_id`),
  KEY `idx_intereses_estado` (`estado`),
  KEY `idx_intereses_fecha` (`fecha_interes`),
  KEY `idx_intereses_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_intereses_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_intereses_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_intereses_nivel` CHECK (`nivel_interes` is null or `nivel_interes` in ('bajo','medio','alto')),
  CONSTRAINT `chk_intereses_estado` CHECK (`estado` in ('activo','descartado','convertido'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellido_paterno` varchar(80) NOT NULL,
  `apellido_materno` varchar(80) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo_interes` varchar(20) DEFAULT NULL,
  `presupuesto_min` decimal(14,2) DEFAULT NULL,
  `presupuesto_max` decimal(14,2) DEFAULT NULL,
  `preferencias` text DEFAULT NULL,
  `estado_cliente` varchar(20) NOT NULL DEFAULT 'prospecto',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clientes_user` (`user_id`),
  KEY `idx_clientes_email` (`email`),
  KEY `idx_clientes_telefono` (`telefono`),
  KEY `idx_clientes_estado` (`estado_cliente`),
  KEY `idx_clientes_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_clientes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_clientes_tipo_interes` CHECK (`tipo_interes` is null or `tipo_interes` in ('compra','renta','ambos')),
  CONSTRAINT `chk_clientes_estado` CHECK (`estado_cliente` in ('prospecto','cliente','inactivo')),
  CONSTRAINT `chk_clientes_presupuesto_min` CHECK (`presupuesto_min` is null or `presupuesto_min` >= 0),
  CONSTRAINT `chk_clientes_presupuesto_max` CHECK (`presupuesto_max` is null or `presupuesto_max` >= 0),
  CONSTRAINT `chk_clientes_rango_presupuesto` CHECK (`presupuesto_min` is null or `presupuesto_max` is null or `presupuesto_max` >= `presupuesto_min`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configuracion_respaldos`
--

DROP TABLE IF EXISTS `configuracion_respaldos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion_respaldos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actualizado_por_user_id` bigint(20) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 0,
  `frecuencia` varchar(20) NOT NULL DEFAULT 'diario',
  `hora_ejecucion` time NOT NULL DEFAULT '02:00:00',
  `dia_semana` tinyint(3) unsigned DEFAULT NULL,
  `dia_mes` tinyint(3) unsigned DEFAULT NULL,
  `retencion_dias` smallint(5) unsigned NOT NULL DEFAULT 30,
  `ruta_destino` varchar(500) DEFAULT NULL,
  `ultima_ejecucion_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_config_respaldos_usuario` (`actualizado_por_user_id`),
  KEY `idx_config_respaldos_activo` (`activo`),
  CONSTRAINT `fk_config_respaldos_usuario` FOREIGN KEY (`actualizado_por_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_config_respaldos_activo` CHECK (`activo` in (0,1)),
  CONSTRAINT `chk_config_respaldos_frecuencia` CHECK (`frecuencia` in ('diario','semanal','mensual')),
  CONSTRAINT `chk_config_respaldos_dia_semana` CHECK (`dia_semana` is null or `dia_semana` between 1 and 7),
  CONSTRAINT `chk_config_respaldos_dia_mes` CHECK (`dia_mes` is null or `dia_mes` between 1 and 28),
  CONSTRAINT `chk_config_respaldos_retencion` CHECK (`retencion_dias` >= 1),
  CONSTRAINT `chk_config_respaldos_programacion` CHECK (`frecuencia` = 'diario' and `dia_semana` is null and `dia_mes` is null or `frecuencia` = 'semanal' and `dia_semana` is not null and `dia_mes` is null or `frecuencia` = 'mensual' and `dia_semana` is null and `dia_mes` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `categoria_documento_id` bigint(20) unsigned NOT NULL,
  `subido_por_user_id` bigint(20) unsigned NOT NULL,
  `propietario_id` bigint(20) unsigned DEFAULT NULL,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `inmueble_id` bigint(20) unsigned DEFAULT NULL,
  `operacion_id` bigint(20) unsigned DEFAULT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `firebase_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `tamano_bytes` bigint(20) unsigned DEFAULT NULL,
  `fecha_documento` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_documentos_categoria` (`categoria_documento_id`),
  KEY `idx_documentos_usuario` (`subido_por_user_id`),
  KEY `idx_documentos_propietario` (`propietario_id`),
  KEY `idx_documentos_cliente` (`cliente_id`),
  KEY `idx_documentos_inmueble` (`inmueble_id`),
  KEY `idx_documentos_operacion` (`operacion_id`),
  KEY `idx_documentos_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_documentos_categoria` FOREIGN KEY (`categoria_documento_id`) REFERENCES `categorias_documentos` (`id`),
  CONSTRAINT `fk_documentos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_documentos_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`),
  CONSTRAINT `fk_documentos_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`),
  CONSTRAINT `fk_documentos_propietario` FOREIGN KEY (`propietario_id`) REFERENCES `propietarios` (`id`),
  CONSTRAINT `fk_documentos_usuario` FOREIGN KEY (`subido_por_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_documentos_tipo_archivo` CHECK (`mime_type` in ('application/pdf','image/png','image/jpeg')),
  CONSTRAINT `chk_documentos_destino` CHECK ((`propietario_id` is not null) + (`cliente_id` is not null) + (`inmueble_id` is not null) + (`operacion_id` is not null) = 1),
  CONSTRAINT `chk_documentos_fechas` CHECK (`fecha_documento` is null or `fecha_vencimiento` is null or `fecha_vencimiento` >= `fecha_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historial_correos`
--

DROP TABLE IF EXISTS `historial_correos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_correos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `destinatario_user_id` bigint(20) unsigned DEFAULT NULL,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `cita_id` bigint(20) unsigned DEFAULT NULL,
  `enviado_por_user_id` bigint(20) unsigned DEFAULT NULL,
  `destinatario_email` varchar(150) NOT NULL,
  `destinatario_nombre` varchar(150) DEFAULT NULL,
  `tipo` varchar(40) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `plantilla` varchar(100) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `proveedor_message_id` varchar(255) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `mensaje_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_correos_destinatario_user` (`destinatario_user_id`),
  KEY `idx_correos_cliente` (`cliente_id`),
  KEY `idx_correos_cita` (`cita_id`),
  KEY `idx_correos_enviado_por` (`enviado_por_user_id`),
  KEY `idx_correos_email` (`destinatario_email`),
  KEY `idx_correos_tipo` (`tipo`),
  KEY `idx_correos_estado` (`estado`),
  KEY `idx_correos_fecha` (`fecha_envio`),
  CONSTRAINT `fk_correos_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_correos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_correos_destinatario_user` FOREIGN KEY (`destinatario_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_correos_enviado_por` FOREIGN KEY (`enviado_por_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_correos_tipo` CHECK (`tipo` in ('confirmacion_cita','reprogramacion_cita','cancelacion_cita','recuperacion_password','respaldo','restauracion','seguridad','otro')),
  CONSTRAINT `chk_correos_estado` CHECK (`estado` in ('pendiente','enviado','fallido'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inmueble_imagenes`
--

DROP TABLE IF EXISTS `inmueble_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inmueble_imagenes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inmueble_id` bigint(20) unsigned NOT NULL,
  `firebase_path` varchar(500) NOT NULL,
  `url_publica` varchar(1000) DEFAULT NULL,
  `nombre_original` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamano_bytes` bigint(20) unsigned DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `texto_alternativo` varchar(180) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_imagenes_inmueble` (`inmueble_id`),
  KEY `idx_imagenes_principal` (`inmueble_id`,`es_principal`),
  KEY `idx_imagenes_orden` (`inmueble_id`,`orden`),
  KEY `idx_imagenes_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_imagenes_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_imagenes_principal` CHECK (`es_principal` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inmuebles`
--

DROP TABLE IF EXISTS `inmuebles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inmuebles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `propietario_id` bigint(20) unsigned NOT NULL,
  `categoria_id` bigint(20) unsigned NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_operacion` varchar(20) NOT NULL,
  `precio_venta` decimal(14,2) DEFAULT NULL,
  `renta_mensual` decimal(14,2) DEFAULT NULL,
  `superficie_terreno_m2` decimal(10,2) DEFAULT NULL,
  `superficie_construccion_m2` decimal(10,2) DEFAULT NULL,
  `habitaciones` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `banos_completos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `medios_banos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `estacionamientos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `niveles` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `calle` varchar(150) NOT NULL,
  `numero_exterior` varchar(20) DEFAULT NULL,
  `numero_interior` varchar(20) DEFAULT NULL,
  `colonia` varchar(100) NOT NULL,
  `municipio` varchar(100) NOT NULL,
  `estado_ubicacion` varchar(100) NOT NULL,
  `codigo_postal` varchar(10) NOT NULL,
  `referencias` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `estado_disponibilidad` varchar(20) NOT NULL DEFAULT 'disponible',
  `publicado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_publicacion` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inmuebles_codigo` (`codigo`),
  UNIQUE KEY `uq_inmuebles_slug` (`slug`),
  KEY `idx_inmuebles_propietario` (`propietario_id`),
  KEY `idx_inmuebles_categoria` (`categoria_id`),
  KEY `idx_inmuebles_tipo_operacion` (`tipo_operacion`),
  KEY `idx_inmuebles_estado` (`estado_disponibilidad`),
  KEY `idx_inmuebles_publicado` (`publicado`),
  KEY `idx_inmuebles_ubicacion` (`estado_ubicacion`,`municipio`,`colonia`),
  KEY `idx_inmuebles_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_inmuebles_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `fk_inmuebles_propietario` FOREIGN KEY (`propietario_id`) REFERENCES `propietarios` (`id`),
  CONSTRAINT `chk_inmuebles_tipo_operacion` CHECK (`tipo_operacion` in ('venta','renta')),
  CONSTRAINT `chk_inmuebles_estado` CHECK (`estado_disponibilidad` in ('disponible','vendido','rentado','inactivo')),
  CONSTRAINT `chk_inmuebles_publicado` CHECK (`publicado` in (0,1)),
  CONSTRAINT `chk_inmuebles_precios` CHECK (`tipo_operacion` = 'venta' and `precio_venta` is not null and `precio_venta` > 0 and `renta_mensual` is null or `tipo_operacion` = 'renta' and `renta_mensual` is not null and `renta_mensual` > 0 and `precio_venta` is null),
  CONSTRAINT `chk_inmuebles_superficie_terreno` CHECK (`superficie_terreno_m2` is null or `superficie_terreno_m2` > 0),
  CONSTRAINT `chk_inmuebles_superficie_construccion` CHECK (`superficie_construccion_m2` is null or `superficie_construccion_m2` >= 0),
  CONSTRAINT `chk_inmuebles_latitud` CHECK (`latitud` is null or `latitud` >= -90 and `latitud` <= 90),
  CONSTRAINT `chk_inmuebles_longitud` CHECK (`longitud` is null or `longitud` >= -180 and `longitud` <= 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interacciones_cliente`
--

DROP TABLE IF EXISTS `interacciones_cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interacciones_cliente` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `registrado_por_user_id` bigint(20) unsigned NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `descripcion` text NOT NULL,
  `resultado` varchar(255) DEFAULT NULL,
  `fecha_interaccion` datetime NOT NULL DEFAULT current_timestamp(),
  `proxima_accion` varchar(255) DEFAULT NULL,
  `fecha_proxima_accion` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_interacciones_cliente` (`cliente_id`),
  KEY `idx_interacciones_usuario` (`registrado_por_user_id`),
  KEY `idx_interacciones_tipo` (`tipo`),
  KEY `idx_interacciones_fecha` (`fecha_interaccion`),
  KEY `idx_interacciones_proxima_fecha` (`fecha_proxima_accion`),
  KEY `idx_interacciones_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_interacciones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_interacciones_usuario` FOREIGN KEY (`registrado_por_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_interacciones_tipo` CHECK (`tipo` in ('llamada','correo','whatsapp','reunion','nota','seguimiento'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `operacion_agentes`
--

DROP TABLE IF EXISTS `operacion_agentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operacion_agentes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operacion_id` bigint(20) unsigned NOT NULL,
  `agente_id` bigint(20) unsigned NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `porcentaje_comision` decimal(5,2) NOT NULL DEFAULT 0.00,
  `monto_comision` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_operacion_agente` (`operacion_id`,`agente_id`),
  KEY `idx_operacion_agentes_operacion` (`operacion_id`),
  KEY `idx_operacion_agentes_agente` (`agente_id`),
  KEY `idx_operacion_agentes_principal` (`operacion_id`,`es_principal`),
  CONSTRAINT `fk_operacion_agentes_agente` FOREIGN KEY (`agente_id`) REFERENCES `agentes` (`id`),
  CONSTRAINT `fk_operacion_agentes_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`),
  CONSTRAINT `chk_operacion_agentes_principal` CHECK (`es_principal` in (0,1)),
  CONSTRAINT `chk_operacion_agentes_porcentaje` CHECK (`porcentaje_comision` >= 0 and `porcentaje_comision` <= 100),
  CONSTRAINT `chk_operacion_agentes_monto` CHECK (`monto_comision` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `operaciones`
--

DROP TABLE IF EXISTS `operaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `oportunidad_id` bigint(20) unsigned NOT NULL,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `inmueble_id` bigint(20) unsigned NOT NULL,
  `registrado_por_user_id` bigint(20) unsigned NOT NULL,
  `tipo_operacion` varchar(20) NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `fecha_operacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_inicio_contrato` date DEFAULT NULL,
  `fecha_fin_contrato` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'registrada',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_operaciones_oportunidad` (`oportunidad_id`),
  KEY `idx_operaciones_cliente` (`cliente_id`),
  KEY `idx_operaciones_inmueble` (`inmueble_id`),
  KEY `idx_operaciones_usuario` (`registrado_por_user_id`),
  KEY `idx_operaciones_tipo` (`tipo_operacion`),
  KEY `idx_operaciones_fecha` (`fecha_operacion`),
  KEY `idx_operaciones_estado` (`estado`),
  CONSTRAINT `fk_operaciones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_operaciones_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`),
  CONSTRAINT `fk_operaciones_oportunidad` FOREIGN KEY (`oportunidad_id`) REFERENCES `oportunidades` (`id`),
  CONSTRAINT `fk_operaciones_usuario` FOREIGN KEY (`registrado_por_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_operaciones_tipo` CHECK (`tipo_operacion` in ('venta','renta')),
  CONSTRAINT `chk_operaciones_monto` CHECK (`monto` > 0),
  CONSTRAINT `chk_operaciones_estado` CHECK (`estado` in ('registrada','anulada')),
  CONSTRAINT `chk_operaciones_fechas_contrato` CHECK (`fecha_inicio_contrato` is null or `fecha_fin_contrato` is null or `fecha_fin_contrato` >= `fecha_inicio_contrato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oportunidad_historial`
--

DROP TABLE IF EXISTS `oportunidad_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidad_historial` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `oportunidad_id` bigint(20) unsigned NOT NULL,
  `cambiado_por_user_id` bigint(20) unsigned NOT NULL,
  `tipo_evento` varchar(30) NOT NULL DEFAULT 'cambio_etapa',
  `etapa_anterior` varchar(30) DEFAULT NULL,
  `etapa_nueva` varchar(30) DEFAULT NULL,
  `estado_anterior` varchar(20) DEFAULT NULL,
  `estado_nuevo` varchar(20) DEFAULT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_historial_oportunidad` (`oportunidad_id`),
  KEY `idx_historial_usuario` (`cambiado_por_user_id`),
  KEY `idx_historial_tipo_evento` (`tipo_evento`),
  KEY `idx_historial_fecha` (`fecha_cambio`),
  CONSTRAINT `fk_historial_oportunidad` FOREIGN KEY (`oportunidad_id`) REFERENCES `oportunidades` (`id`),
  CONSTRAINT `fk_historial_oportunidad_usuario` FOREIGN KEY (`cambiado_por_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_historial_tipo_evento` CHECK (`tipo_evento` in ('creacion','cambio_etapa','cambio_estado')),
  CONSTRAINT `chk_historial_etapa_anterior` CHECK (`etapa_anterior` is null or `etapa_anterior` in ('contacto_inicial','cita','negociacion','documentacion','cierre')),
  CONSTRAINT `chk_historial_etapa_nueva` CHECK (`etapa_nueva` is null or `etapa_nueva` in ('contacto_inicial','cita','negociacion','documentacion','cierre')),
  CONSTRAINT `chk_historial_estado_anterior` CHECK (`estado_anterior` is null or `estado_anterior` in ('activa','ganada','perdida','cancelada')),
  CONSTRAINT `chk_historial_estado_nuevo` CHECK (`estado_nuevo` is null or `estado_nuevo` in ('activa','ganada','perdida','cancelada'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oportunidades`
--

DROP TABLE IF EXISTS `oportunidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `inmueble_id` bigint(20) unsigned DEFAULT NULL,
  `agente_principal_id` bigint(20) unsigned DEFAULT NULL,
  `solicitud_informacion_id` bigint(20) unsigned DEFAULT NULL,
  `titulo` varchar(150) NOT NULL,
  `etapa` varchar(30) NOT NULL DEFAULT 'contacto_inicial',
  `estado` varchar(20) NOT NULL DEFAULT 'activa',
  `notas` text DEFAULT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `motivo_perdida` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_oportunidades_solicitud` (`solicitud_informacion_id`),
  KEY `idx_oportunidades_cliente` (`cliente_id`),
  KEY `idx_oportunidades_inmueble` (`inmueble_id`),
  KEY `idx_oportunidades_agente` (`agente_principal_id`),
  KEY `idx_oportunidades_etapa` (`etapa`),
  KEY `idx_oportunidades_estado` (`estado`),
  KEY `idx_oportunidades_fecha_apertura` (`fecha_apertura`),
  KEY `idx_oportunidades_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_oportunidades_agente` FOREIGN KEY (`agente_principal_id`) REFERENCES `agentes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oportunidades_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_oportunidades_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oportunidades_solicitud` FOREIGN KEY (`solicitud_informacion_id`) REFERENCES `solicitudes_informacion` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_oportunidades_etapa` CHECK (`etapa` in ('contacto_inicial','cita','negociacion','documentacion','cierre')),
  CONSTRAINT `chk_oportunidades_estado` CHECK (`estado` in ('activa','ganada','perdida','cancelada'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `propietarios`
--

DROP TABLE IF EXISTS `propietarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `propietarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_persona` varchar(10) NOT NULL DEFAULT 'fisica',
  `nombre_razon_social` varchar(150) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL,
  `estado_registro` varchar(20) NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_propietarios_rfc` (`rfc`),
  KEY `idx_propietarios_nombre` (`nombre_razon_social`),
  KEY `idx_propietarios_estado` (`estado_registro`),
  KEY `idx_propietarios_deleted_at` (`deleted_at`),
  CONSTRAINT `chk_propietarios_tipo` CHECK (`tipo_persona` in ('fisica','moral')),
  CONSTRAINT `chk_propietarios_estado` CHECK (`estado_registro` in ('activo','inactivo'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `respaldos`
--

DROP TABLE IF EXISTS `respaldos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `respaldos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `generado_por_user_id` bigint(20) unsigned DEFAULT NULL,
  `restaurado_por_user_id` bigint(20) unsigned DEFAULT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'manual',
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tamano_bytes` bigint(20) unsigned DEFAULT NULL,
  `checksum_sha256` char(64) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_finalizacion` datetime DEFAULT NULL,
  `restaurado_at` datetime DEFAULT NULL,
  `estado_restauracion` varchar(20) DEFAULT NULL,
  `mensaje_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_respaldos_generado_por` (`generado_por_user_id`),
  KEY `idx_respaldos_restaurado_por` (`restaurado_por_user_id`),
  KEY `idx_respaldos_tipo` (`tipo`),
  KEY `idx_respaldos_estado` (`estado`),
  KEY `idx_respaldos_fecha` (`fecha_inicio`),
  CONSTRAINT `fk_respaldos_generado_por` FOREIGN KEY (`generado_por_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_respaldos_restaurado_por` FOREIGN KEY (`restaurado_por_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_respaldos_tipo` CHECK (`tipo` in ('manual','automatico')),
  CONSTRAINT `chk_respaldos_estado` CHECK (`estado` in ('pendiente','en_proceso','completado','fallido')),
  CONSTRAINT `chk_respaldos_estado_restauracion` CHECK (`estado_restauracion` is null or `estado_restauracion` in ('en_proceso','completada','fallida')),
  CONSTRAINT `chk_respaldos_fechas` CHECK (`fecha_finalizacion` is null or `fecha_finalizacion` >= `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitudes_informacion`
--

DROP TABLE IF EXISTS `solicitudes_informacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_informacion` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inmueble_id` bigint(20) unsigned DEFAULT NULL,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `atendida_por_user_id` bigint(20) unsigned DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `medio_preferido` varchar(20) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'nueva',
  `origen` varchar(30) NOT NULL DEFAULT 'landing_publica',
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_atencion` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_solicitudes_inmueble` (`inmueble_id`),
  KEY `idx_solicitudes_cliente` (`cliente_id`),
  KEY `idx_solicitudes_usuario` (`atendida_por_user_id`),
  KEY `idx_solicitudes_estado` (`estado`),
  KEY `idx_solicitudes_fecha` (`fecha_solicitud`),
  KEY `idx_solicitudes_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_solicitudes_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_solicitudes_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_solicitudes_usuario` FOREIGN KEY (`atendida_por_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_solicitudes_medio` CHECK (`medio_preferido` is null or `medio_preferido` in ('telefono','whatsapp','correo')),
  CONSTRAINT `chk_solicitudes_estado` CHECK (`estado` in ('nueva','en_atencion','atendida','descartada'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellido_paterno` varchar(80) NOT NULL,
  `apellido_materno` varchar(80) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_estado` (`estado`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  CONSTRAINT `chk_users_estado` CHECK (`estado` in ('pendiente','activo','bloqueado','inactivo'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `visualizaciones_inmuebles`
--

DROP TABLE IF EXISTS `visualizaciones_inmuebles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visualizaciones_inmuebles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inmueble_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_hash` char(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referer` varchar(500) DEFAULT NULL,
  `origen` varchar(30) NOT NULL DEFAULT 'landing_publica',
  `fecha_visualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visualizaciones_inmueble` (`inmueble_id`),
  KEY `idx_visualizaciones_fecha` (`fecha_visualizacion`),
  KEY `idx_visualizaciones_inmueble_fecha` (`inmueble_id`,`fecha_visualizacion`),
  KEY `idx_visualizaciones_origen` (`origen`),
  CONSTRAINT `fk_visualizaciones_inmueble` FOREIGN KEY (`inmueble_id`) REFERENCES `inmuebles` (`id`),
  CONSTRAINT `chk_visualizaciones_origen` CHECK (`origen` in ('landing_publica','portal_cliente','interno'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-24 20:45:35
