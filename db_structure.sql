CREATE TABLE `logs` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `source` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `level` smallint(6) NOT NULL DEFAULT 400,
  `level_name` varchar(50) NOT NULL DEFAULT 'error',
  `context` text,
  `channel` varchar(100) DEFAULT NULL,
  `extra` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `isld_idx` (`log_id`,`source`,`level`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;