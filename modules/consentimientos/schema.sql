-- Tabla para almacenar los modelos de consentimientos disponibles
CREATE TABLE IF NOT EXISTS `consentimientos_modelos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `contenido` text NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para registrar los consentimientos enviados a pacientes
CREATE TABLE IF NOT EXISTS `consentimientos_enviados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `modelo_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `estado` enum('pendiente','firmado','caducado') NOT NULL DEFAULT 'pendiente',
  `email` varchar(255) NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_firma` timestamp NULL DEFAULT NULL,
  `fecha_caducidad` timestamp NULL DEFAULT NULL,
  `ip_firma` varchar(45) DEFAULT NULL,
  `enviado_por` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `paciente_id` (`paciente_id`),
  KEY `modelo_id` (`modelo_id`),
  KEY `enviado_por` (`enviado_por`),
  CONSTRAINT `fk_consentimientos_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consentimientos_modelo` FOREIGN KEY (`modelo_id`) REFERENCES `consentimientos_modelos` (`id`),
  CONSTRAINT `fk_consentimientos_usuario` FOREIGN KEY (`enviado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para almacenar las firmas de los consentimientos
CREATE TABLE IF NOT EXISTS `consentimientos_firmas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consentimiento_id` int(11) NOT NULL,
  `firma_imagen` mediumtext NOT NULL,
  `nombre_firmante` varchar(255) NOT NULL,
  `fecha_firma` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `consentimiento_id` (`consentimiento_id`),
  CONSTRAINT `fk_firma_consentimiento` FOREIGN KEY (`consentimiento_id`) REFERENCES `consentimientos_enviados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 