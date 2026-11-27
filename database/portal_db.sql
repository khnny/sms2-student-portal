-- SMS2 Student Portal database (separate from sms2_db / CRAD).
-- The portal creates these tables automatically; this file is for phpMyAdmin imports.

CREATE TABLE IF NOT EXISTS `portal_users` (
  `user_id` int(10) unsigned NOT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `idx_portal_users_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portal_webhook_events` (
  `event_id` char(36) NOT NULL,
  `event_type` varchar(80) NOT NULL,
  `payload` mediumtext NOT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `idx_portal_events_type` (`event_type`, `received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portal_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` char(36) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_portal_notif_user` (`user_id`, `created_at`),
  KEY `idx_portal_notif_student` (`student_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
