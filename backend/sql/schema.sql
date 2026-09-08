-- ============================================
-- Схема базы данных для Clinic Schedule System
-- ============================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE,
  `token` VARCHAR(500),
  `role` ENUM('admin', 'doctor', 'user') DEFAULT 'user',
  `is_active` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица записей пациентов
CREATE TABLE IF NOT EXISTS `records` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  `time` TIME NOT NULL,
  `phone` VARCHAR(20),
  `service` VARCHAR(255),
  `notes` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_date` (`date`),
  INDEX `idx_created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица заметок
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов резервного копирования
CREATE TABLE IF NOT EXISTS `backup_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `backup_type` ENUM('manual', 'auto') DEFAULT 'manual',
  `file_path` VARCHAR(500),
  `file_size` INT,
  `status` ENUM('success', 'failed') DEFAULT 'success',
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов действий
CREATE TABLE IF NOT EXISTS `action_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `action` VARCHAR(255),
  `table_name` VARCHAR(100),
  `record_id` INT,
  `old_value` JSON,
  `new_value` JSON,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- НАЧАЛЬНЫЕ ДАННЫЕ (тестовые аккаунты)
-- ============================================

-- Администратор: admin / admin123
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `email`, `role`, `is_active`) VALUES
(1, 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'admin@clinic.local', 'admin', 1);

-- Врач: doctor / doctor123
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `email`, `role`, `is_active`) VALUES
(2, 'doctor', '$2y$10$V0QjOyxDY8Cs1YZ.YrJrK.u3Vb0LGE3FpVxaLy4qVqRVvGkJ6cJdm', 'doctor@clinic.local', 'doctor', 1);

-- ============================================
-- Триггеры для автоматического логирования
-- ============================================

DELIMITER //

CREATE TRIGGER IF NOT EXISTS `log_records_insert` AFTER INSERT ON `records`
FOR EACH ROW
BEGIN
  INSERT INTO `action_logs` (`user_id`, `action`, `table_name`, `record_id`, `new_value`, `ip_address`)
  VALUES (NEW.`created_by`, 'INSERT', 'records', NEW.`id`, JSON_OBJECT('name', NEW.`name`, 'date', NEW.`date`, 'service', NEW.`service`), INET_ATON(IFNULL(@@session.sql_mode, '0')));
END//

CREATE TRIGGER IF NOT EXISTS `log_records_delete` AFTER DELETE ON `records`
FOR EACH ROW
BEGIN
  INSERT INTO `action_logs` (`action`, `table_name`, `record_id`, `old_value`, `ip_address`)
  VALUES ('DELETE', 'records', OLD.`id`, JSON_OBJECT('name', OLD.`name`, 'date', OLD.`date`, 'service', OLD.`service`), INET_ATON(IFNULL(@@session.sql_mode, '0')));
END//

DELIMITER ;
