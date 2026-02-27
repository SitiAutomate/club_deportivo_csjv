-- Schema inicial para Inscripciones Club
-- Ejecutar en MySQL/MariaDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla responsable (padre de participante)
CREATE TABLE IF NOT EXISTS `responsable` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `documento` varchar(32) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_responsable_documento` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla participante
CREATE TABLE IF NOT EXISTS `participante` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `documento` varchar(32) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `responsable_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_participante_documento` (`documento`),
  KEY `fk_participante_responsable` (`responsable_id`),
  CONSTRAINT `fk_participante_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `responsable` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tipos de inscripción (1=cursos, 2=campamentos, 3=salidas)
CREATE TABLE IF NOT EXISTS `inscripcion_tipo` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inscripcion_tipo_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inscripcion_tipo` (`id`, `nombre`, `slug`) VALUES
(1, 'Cursos', 'cursos'),
(2, 'Campamentos', 'campamentos'),
(3, 'Salidas', 'salidas');

-- Tabla cursos (opciones para tipo 1)
CREATE TABLE IF NOT EXISTS `curso` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla campamentos (opciones para tipo 2)
CREATE TABLE IF NOT EXISTS `campamento` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla salidas (opciones para tipo 3)
CREATE TABLE IF NOT EXISTS `salida` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `fecha` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla inscripción (cabecera)
CREATE TABLE IF NOT EXISTS `inscripcion` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `participante_id` int(11) UNSIGNED NOT NULL,
  `responsable_id` int(11) UNSIGNED NOT NULL,
  `tipo_id` int(11) UNSIGNED NOT NULL,
  `fecha_inscripcion` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_inscripcion_participante` (`participante_id`),
  KEY `fk_inscripcion_responsable` (`responsable_id`),
  KEY `fk_inscripcion_tipo` (`tipo_id`),
  CONSTRAINT `fk_inscripcion_participante` FOREIGN KEY (`participante_id`) REFERENCES `participante` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inscripcion_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `responsable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inscripcion_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `inscripcion_tipo` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle inscripción curso
CREATE TABLE IF NOT EXISTS `inscripcion_curso` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` int(11) UNSIGNED NOT NULL,
  `curso_id` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inscripcion_curso` (`inscripcion_id`, `curso_id`),
  KEY `fk_ic_curso` (`curso_id`),
  CONSTRAINT `fk_ic_inscripcion` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripcion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ic_curso` FOREIGN KEY (`curso_id`) REFERENCES `curso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle inscripción campamento
CREATE TABLE IF NOT EXISTS `inscripcion_campamento` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` int(11) UNSIGNED NOT NULL,
  `campamento_id` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inscripcion_campamento` (`inscripcion_id`, `campamento_id`),
  KEY `fk_icamp_campamento` (`campamento_id`),
  CONSTRAINT `fk_icamp_inscripcion` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripcion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_icamp_campamento` FOREIGN KEY (`campamento_id`) REFERENCES `campamento` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle inscripción salida
CREATE TABLE IF NOT EXISTS `inscripcion_salida` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscripcion_id` int(11) UNSIGNED NOT NULL,
  `salida_id` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inscripcion_salida` (`inscripcion_id`, `salida_id`),
  KEY `fk_isal_salida` (`salida_id`),
  CONSTRAINT `fk_isal_inscripcion` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripcion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_isal_salida` FOREIGN KEY (`salida_id`) REFERENCES `salida` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
