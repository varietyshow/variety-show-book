CREATE TABLE IF NOT EXISTS `verification_tokens` (
    `token_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `token` varchar(64) NOT NULL,
    `expiry` datetime NOT NULL,
    PRIMARY KEY (`token_id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customer_account`(`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
