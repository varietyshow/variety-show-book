-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2024 at 06:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_account`
--

CREATE TABLE `admin_account` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(15) NOT NULL,
  `password` varchar(20) NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `last_name` varchar(75) NOT NULL,
  `contact_number` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_account`
--

INSERT INTO `admin_account` (`admin_id`, `username`, `password`, `first_name`, `last_name`, `contact_number`) VALUES
(1, 'admin', '1234', 'admin1', 'admin1', '09654433242');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL,
  `mobile_number` varchar(11) NOT NULL,
  `name` varchar(75) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`billing_id`, `admin_id`, `logo`, `payment_method`, `mobile_number`, `name`, `qr_code`) VALUES
(20, 0, NULL, 'paypal', '09651233453', 'Mark Latonio', '../images/paypal.png'),
(21, 0, NULL, 'maya', '09651233453', 'Mark Latonio', '../images/maya.png'),
(22, 0, NULL, 'gcash', '09651233453', 'Mark Latonio', '../images/gcash.png');

-- --------------------------------------------------------

--
-- Table structure for table `booking_report`
--

CREATE TABLE `booking_report` (
  `book_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `entertainer_id` int(11) DEFAULT NULL,
  `sched_id` int(11) DEFAULT NULL,
  `first_name` varchar(75) DEFAULT NULL,
  `last_name` varchar(75) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `date_schedule` date DEFAULT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `entertainer_name` varchar(255) DEFAULT NULL,
  `roles` varchar(255) NOT NULL,
  `perform_durations` varchar(500) NOT NULL,
  `total_price` decimal(50,0) NOT NULL,
  `down_payment` decimal(50,0) NOT NULL,
  `balance` decimal(50,0) NOT NULL,
  `payment_image` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Declined','Cancelled') DEFAULT NULL,
  `reason` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_report`
--

INSERT INTO `booking_report` (`book_id`, `customer_id`, `entertainer_id`, `sched_id`, `first_name`, `last_name`, `contact_number`, `street`, `barangay`, `municipality`, `province`, `date_schedule`, `time_start`, `time_end`, `entertainer_name`, `roles`, `perform_durations`, `total_price`, `down_payment`, `balance`, `payment_image`, `status`, `reason`) VALUES
(99, 1, 1, NULL, 'mark', 'latonio', '09656533489', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2024-12-18', '18:28:00', '20:28:00', 'Marcy Latonio, entertainer2 entertainer2', 'Comedy Clown,Pop Girl,singer,Comedy Clown,Fire Dance,magician,singer', 'Comedy Clown: 0 hour, Pop Girl: 0 hour, singer: 0 song, Comedy Clown: 0 hour, Fire Dance: 0 hour, magician: 0 hour, singer: 0 song', 6100, 3050, 3050, '../images/675b7fdf98dcc.jpg', 'Declined', 'i am sorry'),
(100, 1, 1, NULL, 'mark', 'latonio', '09656533489', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2024-12-20', '18:35:00', '20:35:00', 'Marcy Latonio, entertainer2 entertainer2', 'Comedy Clown,Pop Girl,singer,Comedy Clown,Fire Dance,magician,singer', 'Comedy Clown: 0 hour, Pop Girl: 0 hour, singer: 0 song, Comedy Clown: 0 hour, Fire Dance: 0 hour, magician: 0 hour, singer: 0 song', 6300, 3150, 3150, '../images/675b816578387.jpg', 'Declined', 'please work'),
(101, 1, 1, NULL, 'mark', 'latonio', '09656533489', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2024-12-17', '18:40:00', '20:40:00', 'Marcy Latonio, entertainer2 entertainer2', 'Comedy Clown,Pop Girl,singer,Comedy Clown,Fire Dance,magician,singer', 'Comedy Clown: 0 hour, Pop Girl: 0 hour, singer: 0 song, Comedy Clown: 0 hour, Fire Dance: 0 hour, magician: 0 hour, singer: 0 song', 5900, 2950, 2950, '../images/675b829c67744.jpg', 'Declined', 'wasad'),
(102, 1, 1, NULL, 'mark', 'latonio', '09656533489', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2024-12-18', '18:45:00', '20:45:00', 'Marcy Latonio, entertainer2 entertainer2', 'Comedy Clown,Pop Girl,singer,Comedy Clown,Fire Dance,magician,singer', 'Comedy Clown: 1 hour, Pop Girl: 1 hour, singer: 1 song, Fire Dance: 1 hour, magician: 1 hour', 5900, 2950, 2950, '../images/675b83e5b96f8.jpg', 'Declined', 'i am sorry senpai'),
(103, 1, 1, NULL, 'mark', 'latonio', '09656533489', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2024-12-18', '18:25:00', '20:25:00', 'Marcy Latonio, entertainer2 entertainer2', 'Comedy Clown,Pop Girl,singer,Comedy Clown,Fire Dance,magician,singer', '', 5900, 2950, 2950, '../images/675b9b51171a5.jpg', 'Declined', 'i am sorry senpai'),
(104, 1, 1, NULL, 'mark', 'latonio', '09656533489', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2024-12-19', '18:31:00', '20:31:00', 'Marcy Latonio, entertainer2 entertainer2', 'Comedy Clown,Pop Girl,singer,Comedy Clown,Fire Dance,magician,singer', 'Comedy Clown: 1 hour, Pop Girl: 1 hour, singer: 1 song, Comedy Clown: 1 hour, Fire Dance: 1 hour, magician: 1 hour, singer: 1 song', 5900, 2950, 2950, '../images/675b9cb3ccefc.jpg', 'Approved', '');

-- --------------------------------------------------------

--
-- Table structure for table `customer_account`
--

CREATE TABLE `customer_account` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `sex` enum('male','female') NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) NOT NULL,
  `building` varchar(50) DEFAULT NULL,
  `street` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `agreed_to_terms` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_account`
--

INSERT INTO `customer_account` (`customer_id`, `first_name`, `last_name`, `sex`, `birthdate`, `age`, `building`, `street`, `barangay`, `city`, `province`, `email`, `username`, `password`, `agreed_to_terms`, `created_at`) VALUES
(1, 'customer', 'customer', 'male', '2014-09-30', 0, NULL, '', '', '', '', '', 'customer', '123', 0, '2024-09-06 01:52:49'),
(3, 'customer2', 'customer2', 'male', '2014-09-24', 0, NULL, '', '', '', '', 'test@gmail.com', 'customer2', '123', 0, '2024-09-06 01:56:20');

-- --------------------------------------------------------

--
-- Table structure for table `entertainer_account`
--

CREATE TABLE `entertainer_account` (
  `entertainer_id` int(11) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `username` varchar(15) NOT NULL,
  `password` varchar(20) NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `title` varchar(50) NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `roles` varchar(50) NOT NULL,
  `street` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `municipality` varchar(50) NOT NULL,
  `province` varchar(100) NOT NULL,
  `facebook_acc` varchar(100) NOT NULL,
  `instagram_acc` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entertainer_account`
--

INSERT INTO `entertainer_account` (`entertainer_id`, `profile_image`, `username`, `password`, `first_name`, `last_name`, `title`, `contact_number`, `roles`, `street`, `barangay`, `municipality`, `province`, `facebook_acc`, `instagram_acc`, `status`) VALUES
(1, 'images.jpg', 'entertainer', '123', 'Markyyyy', 'Latonio', 'The Mighty Band', '09862312445', 'Fire Dance,Pop Girl', 'magsaysay', 'culo', 'tambulig', 'zamboanga del sur', 'https://www.facebook.com/', 'https://www.instagram.com/', 'Active'),
(2, 'sample.jpg', 'entertainer2', '123', 'entertainer2', 'entertainer2', 'The Singerist', '0', 'singer,magician,Comedy Clown,Fire Dance', '', '', '', '', '', '', 'Active'),
(3, 'sample.jpg', 'entertainer3', '123', 'entertainer3', 'entertainer3', 'The Bugeyman', '0', '', '', '', '', '', '', '', 'Active'),
(6, 'images.jpg', 'comedyboxer', '12345', 'Markyyyy', 'Latonio', 'Comedy Boxer', '09862312445', '', 'magsaysay', 'culo', 'tambulig', 'zamboanga del sur', 'https://www.facebook.com/', 'https://www.instagram.com/', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `duration_unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `admin_id`, `role_name`, `rate`, `duration`, `duration_unit`) VALUES
(5, 0, 'Singer', 200.00, '1', 'song'),
(7, 0, 'Magician', 1000.00, '1', 'hour'),
(9, 0, 'Comedy Clown', 1000.00, '1', 'hour'),
(10, 0, 'Fire Dance', 1000.00, '1', 'hour'),
(11, 0, 'Pop Girl', 1500.00, '1', 'hour'),
(12, 0, 'Comedy Boxing', 1000.00, '1', 'hour');

-- --------------------------------------------------------

--
-- Table structure for table `sched_time`
--

CREATE TABLE `sched_time` (
  `sched_id` int(11) NOT NULL,
  `entertainer_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('Available','Booked','Unavailable') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sched_time`
--

INSERT INTO `sched_time` (`sched_id`, `entertainer_id`, `date`, `start_time`, `end_time`, `price`, `status`) VALUES
(77, 1, '2024-12-17', '17:31:00', '22:31:00', 1000.00, 'Available'),
(78, 1, '2024-12-18', '17:31:00', '22:31:00', 1000.00, 'Available'),
(79, 1, '2024-12-19', '17:31:00', '22:31:00', 1000.00, 'Available'),
(80, 1, '2024-12-20', '17:31:00', '22:31:00', 1000.00, 'Available'),
(81, 1, '2024-12-21', '17:31:00', '22:31:00', 1000.00, 'Available'),
(82, 1, '2024-12-22', '17:31:00', '22:31:00', 1000.00, 'Available'),
(83, 1, '2024-12-23', '17:31:00', '22:31:00', 1000.00, 'Available'),
(84, 1, '2024-12-24', '17:31:00', '22:31:00', 1000.00, 'Available'),
(85, 1, '2024-12-25', '17:31:00', '22:31:00', 1000.00, 'Available'),
(86, 1, '2024-12-26', '17:31:00', '22:31:00', 1000.00, 'Available'),
(87, 1, '2024-12-27', '17:31:00', '22:31:00', 1000.00, 'Available'),
(88, 1, '2024-12-28', '17:31:00', '22:31:00', 1000.00, 'Available'),
(91, 2, '2024-12-16', '16:20:00', '23:21:00', 1000.00, 'Available'),
(92, 2, '2024-12-17', '16:20:00', '23:21:00', 1000.00, 'Available'),
(93, 2, '2024-12-18', '16:20:00', '23:21:00', 1000.00, 'Available'),
(102, 1, '2024-12-29', '04:35:00', '16:35:00', 0.00, 'Available'),
(103, 1, '2024-12-30', '04:35:00', '16:35:00', 0.00, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `upload_id` int(11) NOT NULL,
  `entertainer_id` int(11) NOT NULL,
  `filename` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`upload_id`, `entertainer_id`, `filename`) VALUES
(14, 1, '676044d619767_1734362326.mp4'),
(15, 1, '676044e87e6f1_1734362344.mp4');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_account`
--
ALTER TABLE `admin_account`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`billing_id`);

--
-- Indexes for table `booking_report`
--
ALTER TABLE `booking_report`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `fk_customer` (`customer_id`),
  ADD KEY `fk_entertainer` (`entertainer_id`),
  ADD KEY `sched_id` (`sched_id`);

--
-- Indexes for table `customer_account`
--
ALTER TABLE `customer_account`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `entertainer_account`
--
ALTER TABLE `entertainer_account`
  ADD PRIMARY KEY (`entertainer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `sched_time`
--
ALTER TABLE `sched_time`
  ADD PRIMARY KEY (`sched_id`),
  ADD UNIQUE KEY `unique_schedule` (`entertainer_id`,`date`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`upload_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_account`
--
ALTER TABLE `admin_account`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `booking_report`
--
ALTER TABLE `booking_report`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `customer_account`
--
ALTER TABLE `customer_account`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `entertainer_account`
--
ALTER TABLE `entertainer_account`
  MODIFY `entertainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sched_time`
--
ALTER TABLE `sched_time`
  MODIFY `sched_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking_report`
--
ALTER TABLE `booking_report`
  ADD CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_account` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_entertainer` FOREIGN KEY (`entertainer_id`) REFERENCES `entertainer_account` (`entertainer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
