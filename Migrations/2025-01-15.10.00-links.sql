CREATE TABLE
	IF NOT EXISTS `featherpanel_billinglinks_links` (
		`id` INT (11) NOT NULL AUTO_INCREMENT,
		`code` VARCHAR(255) NOT NULL COMMENT 'Unique code/UUID for the link',
		`user_id` INT (11) NOT NULL COMMENT 'User who created the link',
		`provider` VARCHAR(50) NOT NULL COMMENT 'Link provider (linkvertise, shareus, linkpays, gyanilinks)',
		`completed` ENUM('false', 'true') NOT NULL DEFAULT 'false' COMMENT 'Whether the link has been completed',
		`deleted` ENUM('false', 'true') NOT NULL DEFAULT 'false' COMMENT 'Soft delete flag',
		`locked` ENUM('false', 'true') NOT NULL DEFAULT 'false' COMMENT 'Lock flag',
		`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `billinglinks_links_code_unique` (`code`),
		KEY `idx_user_id` (`user_id`),
		KEY `idx_provider` (`provider`),
		KEY `idx_created_at` (`created_at`),
		KEY `idx_completed` (`completed`),
		CONSTRAINT `billinglinks_links_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `featherpanel_users` (`id`) ON DELETE CASCADE
	) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


