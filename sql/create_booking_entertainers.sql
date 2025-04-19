-- Create booking_entertainers table
CREATE TABLE IF NOT EXISTS `booking_entertainers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `entertainer_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_booking` (`book_id`),
  KEY `fk_entertainer_assign` (`entertainer_id`),
  CONSTRAINT `fk_booking` FOREIGN KEY (`book_id`) REFERENCES `booking_report` (`book_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entertainer_assign` FOREIGN KEY (`entertainer_id`) REFERENCES `entertainer_account` (`entertainer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
