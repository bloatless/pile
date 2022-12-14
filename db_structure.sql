CREATE TABLE `logs` (
    `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `source` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `level` smallint(6) NOT NULL DEFAULT 400,
    `level_name` varchar(50) NOT NULL DEFAULT 'error',
    `context` text DEFAULT NULL,
    `channel` varchar(100) DEFAULT NULL,
    `extra` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`log_id`),
    KEY `isld_idx` (`log_id`,`source`,`level`,`created_at`),
    KEY `level_idx` (`level`,`level_name`) USING BTREE,
    KEY `source_idx` (`source`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;