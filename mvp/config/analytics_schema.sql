-- Analytics tables

-- Analytics Metrics table
CREATE TABLE IF NOT EXISTS `{prefix}biz_analytics_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `metric` varchar(50) NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `metric` (`metric`),
  KEY `business_id` (`business_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_metric_business` FOREIGN KEY (`business_id`) REFERENCES `{prefix}biz_businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Analytics Searches table
CREATE TABLE IF NOT EXISTS `{prefix}biz_analytics_searches` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `query` varchar(255) NOT NULL,
  `filters` json DEFAULT NULL,
  `results` int UNSIGNED DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `query` (`query`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
