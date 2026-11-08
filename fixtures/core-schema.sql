-- Schema only, no copied member or production data.
CREATE TABLE IF NOT EXISTS `lug_approval` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `invID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `pab` decimal(11,1) NOT NULL,
  `user` bigint(22) NOT NULL,
  `logtime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text NOT NULL,
  `type` varchar(22) NOT NULL,
  `req` int(255) NOT NULL,
  `status` varchar(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_checkout_cart_holds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL DEFAULT 1,
  `preferred_location_id` smallint(6) NOT NULL DEFAULT 0,
  `event_id` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_inventory` (`user_id`,`inventory_id`),
  KEY `idx_inventory_id` (`inventory_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_checkout_request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL DEFAULT 1,
  `quantity_approved` int(11) NOT NULL DEFAULT 0,
  `quantity_returned` int(11) NOT NULL DEFAULT 0,
  `preferred_location_id` smallint(6) NOT NULL DEFAULT 0,
  `fulfilled_location_id` smallint(6) NOT NULL DEFAULT 0,
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `approval_user_id` bigint(11) DEFAULT NULL,
  `approved_by` bigint(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `admin_notes` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_checkout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` bigint(11) NOT NULL,
  `event_id` int(11) NOT NULL DEFAULT 0,
  `status` varchar(24) NOT NULL DEFAULT 'draft',
  `notes` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requester_id` (`requester_id`),
  KEY `event_id` (`event_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_cmeta` (
  `cmeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`cmeta_id`),
  KEY `user_id` (`cat_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_custody_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `request_item_id` int(11) DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `from_holder_id` bigint(11) NOT NULL DEFAULT 0,
  `to_holder_id` bigint(11) NOT NULL DEFAULT 0,
  `from_location_id` smallint(6) NOT NULL DEFAULT 0,
  `to_location_id` smallint(6) NOT NULL DEFAULT 0,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `quantity_before` int(11) NOT NULL DEFAULT 0,
  `quantity_after` int(11) NOT NULL DEFAULT 0,
  `display_label` varchar(180) NOT NULL DEFAULT '',
  `event_id` int(11) DEFAULT NULL,
  `acted_by` bigint(11) NOT NULL,
  `notes` text NOT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `request_id` (`request_id`),
  KEY `event_id` (`event_id`),
  KEY `logged_at` (`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_event_meta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `field_key` varchar(64) NOT NULL,
  `field_value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_field` (`event_id`,`field_key`),
  KEY `field_key` (`field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_events` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `attendees` mediumint(9) DEFAULT 0,
  `creator` int(9) NOT NULL,
  `url` text NOT NULL,
  `status` varchar(11) NOT NULL DEFAULT 'created',
  `classification` varchar(255) NOT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `total_attendance` mediumint(9) DEFAULT NULL,
  `percent_kids` float DEFAULT NULL,
  `percent_teens` float DEFAULT NULL,
  `percent_adults` float DEFAULT NULL,
  `entrance_fee` varchar(255) DEFAULT NULL,
  `co_created_with` varchar(255) DEFAULT NULL,
  `lan_support_used` tinyint(1) DEFAULT NULL,
  `social_links` text DEFAULT NULL,
  `lego_office_involvement` tinyint(1) DEFAULT NULL,
  `community_enjoyment` text DEFAULT NULL,
  `gcal_event_id` varchar(255) DEFAULT NULL,
  `gcal_sync_status` varchar(32) DEFAULT 'not_pushed',
  `gcal_last_synced_at` datetime DEFAULT NULL,
  `gcal_last_error` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_events_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_shift` (`user_id`,`shift`),
  UNIQUE KEY `user_shift` (`user_id`,`shift`),
  KEY `idx_user_shift` (`user_id`,`shift`),
  KEY `shift` (`shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_events_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '',
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime NOT NULL DEFAULT current_timestamp(),
  `event` bigint(20) unsigned NOT NULL DEFAULT 0,
  `cups` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cap` int(11) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `displaydate` tinyint(3) NOT NULL DEFAULT 3,
  PRIMARY KEY (`id`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_event` (`event`),
  KEY `event` (`event`),
  KEY `start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `cat_id` bigint(22) NOT NULL,
  `description` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` text NOT NULL,
  `status` int(1) NOT NULL,
  `owner` bigint(11) NOT NULL,
  `added` timestamp NOT NULL DEFAULT current_timestamp(),
  `addedby` bigint(22) NOT NULL,
  `home_location_id` smallint(6) NOT NULL DEFAULT 0,
  `home_holder_id` bigint(11) NOT NULL DEFAULT 0,
  `current_holder_id` bigint(11) NOT NULL DEFAULT 0,
  `current_location_id` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory_archive` (
  `id` int(20) NOT NULL,
  `archid` int(20) NOT NULL AUTO_INCREMENT,
  `cat_id` bigint(22) NOT NULL,
  `description` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` text NOT NULL,
  `status` int(1) NOT NULL,
  `added` timestamp NOT NULL DEFAULT current_timestamp(),
  `addedby` bigint(22) NOT NULL,
  PRIMARY KEY (`archid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `desc` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory_holdings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` int(10) unsigned NOT NULL,
  `holder_user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `location_id` smallint(5) unsigned NOT NULL DEFAULT 0,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `source_request_item_id` int(10) unsigned NOT NULL DEFAULT 0,
  `context` enum('storage','checked_out','transit') NOT NULL DEFAULT 'storage',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventory` (`inventory_id`),
  KEY `idx_holder` (`holder_user_id`),
  KEY `idx_location` (`location_id`),
  KEY `idx_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory_location_quantities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `location_id` smallint(6) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_inv_loc` (`inventory_id`,`location_id`),
  KEY `idx_inventory_id` (`inventory_id`),
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory_locations` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `label` varchar(80) NOT NULL,
  `contact_user_id` bigint(11) NOT NULL DEFAULT 0,
  `notes` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `lug_inventory_meta` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `iid` int(11) NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`mid`),
  UNIQUE KEY `mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_log` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `invID` int(11) NOT NULL,
  `details` varchar(255) NOT NULL,
  `user` bigint(22) NOT NULL,
  `logtime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_pab_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amt` float NOT NULL,
  `user` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `class` text NOT NULL,
  `added_by` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_pabtracker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `datefrom` date DEFAULT NULL,
  `dateto` date DEFAULT NULL,
  `contributors` text DEFAULT NULL,
  `staff` text DEFAULT NULL,
  `public` int(11) DEFAULT NULL,
  `members` int(11) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `redeem` tinyint(1) DEFAULT 0,
  `support` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `who` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lug_pabtracker_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `desc` text NOT NULL,
  `mid` int(11) NOT NULL,
  `type` varchar(12) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `who` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

