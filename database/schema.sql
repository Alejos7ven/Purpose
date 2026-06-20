CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `estado` VARCHAR(20) DEFAULT 'pendiente',
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) DEFAULT NULL,
  `tlf` VARCHAR(50) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `mensaje_registro` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proyectos_vida` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `fecha_noviazgo` DATE DEFAULT NULL,
  `fecha_compromiso` DATE DEFAULT NULL,
  `fecha_matrimonio` DATE DEFAULT NULL,
  `creador_id` INT NOT NULL,
  `pareja_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pareja_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventario` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `proyecto_id` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  `categoria` VARCHAR(100) DEFAULT NULL,
  `estado` VARCHAR(50) DEFAULT NULL,
  `valor` DECIMAL(10,2) DEFAULT NULL,
  `emoji` VARCHAR(50) DEFAULT NULL,
  `creado_por` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos_vida` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `necesidades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `proyecto_id` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  `categoria` VARCHAR(100) DEFAULT NULL,
  `prioridad` VARCHAR(50) DEFAULT 'Media',
  `adquirido` TINYINT(1) NOT NULL DEFAULT 0,
  `presupuesto` DECIMAL(10,2) DEFAULT NULL,
  `emoji` VARCHAR(50) DEFAULT NULL,
  `creado_por` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos_vida` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial users
INSERT INTO `usuarios` (`email`, `password`, `nombre`) VALUES
('demarzomonterojose@gmail.com', '$2y$10$HXRSC4rb5CsLVb3/xBRX2.G2H7sAvCXTzFcrM1RmyygD/xe.Rbjo2', 'Jose'),
('marlysugc@gmail.com', '$2y$10$HXRSC4rb5CsLVb3/xBRX2.G2H7sAvCXTzFcrM1RmyygD/xe.Rbjo2', 'Marlys')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

CREATE TABLE IF NOT EXISTS `feed_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `proyecto_vida_id` INT NOT NULL,
  `autor_usuario_id` INT NOT NULL,
  `contenido` TEXT NOT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `shared_post_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`proyecto_vida_id`) REFERENCES `proyectos_vida` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`autor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shared_post_id`) REFERENCES `feed_posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feed_comentarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `proyecto_vida_id` INT NOT NULL,
  `autor_usuario_id` INT NOT NULL,
  `contenido` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`proyecto_vida_id`) REFERENCES `proyectos_vida` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`autor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feed_reacciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `proyecto_vida_id` INT NOT NULL,
  `autor_usuario_id` INT NOT NULL,
  `tipo` VARCHAR(20) NOT NULL DEFAULT 'me_gusta',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_post_proyecto` (`post_id`, `proyecto_vida_id`),
  FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`proyecto_vida_id`) REFERENCES `proyectos_vida` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`autor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

