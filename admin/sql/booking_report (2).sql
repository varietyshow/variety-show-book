-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2025 at 08:28 PM
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
-- Table structure for table `booking_report`
--

CREATE TABLE `booking_report` (
  `book_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
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
  `package` varchar(255) NOT NULL,
  `total_price` decimal(50,0) NOT NULL,
  `down_payment` decimal(50,0) NOT NULL,
  `balance` decimal(50,0) NOT NULL,
  `payment_image` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Declined','Cancelled') DEFAULT NULL,
  `remarks` enum('Pending','Complete') NOT NULL,
  `reason` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_report`
--

INSERT INTO `booking_report` (`book_id`, `admin_id`, `customer_id`, `entertainer_id`, `sched_id`, `first_name`, `last_name`, `contact_number`, `street`, `barangay`, `municipality`, `province`, `date_schedule`, `time_start`, `time_end`, `entertainer_name`, `roles`, `perform_durations`, `package`, `total_price`, `down_payment`, `balance`, `payment_image`, `status`, `remarks`, `reason`) VALUES
(113, 0, 5, 1, NULL, 'marky', 'latonio', '09456783274', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2025-01-17', '09:00:00', '11:00:00', 'Markyyyy Latonio', 'Comedy Clown,mascot', 'Comedy Clown: 1 hour, mascot: 2 appearance', '', 2000, 1000, 1000, '../images/6781620882b64.jpg', 'Approved', 'Complete', ''),
(114, 0, 5, 1, NULL, 'customer3', 'latonio', '09674435322', 'magsaysay', 'culo', 'molave', 'zamboanga del sur', '2025-01-16', '09:00:00', '11:00:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 2 appearance', '', 3000, 1500, 1500, '../images/6781a9732ddcc.jpg', 'Declined', 'Pending', 'wew'),
(115, 0, 1, 1, NULL, 'Customer', 'Customer', '09432336109', 'purok 1', 'dalawon', 'tambulig', 'Zamboanga del Sur', '2025-01-25', '16:00:00', '21:00:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 2 appearance', '', 3000, 1500, 1500, '../images/6782eb1793165.jpg', 'Cancelled', 'Pending', 'gaulan'),
(116, 0, 3, 1, NULL, 'customer2', 'customer2', '09875654323', 'purok2', 'culo', 'molave', 'Zamboanga del Sur', '2025-01-14', '14:15:00', '16:00:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 1 appearance', '', 2500, 1250, 1250, '../images/6784697e59ea5.jpg', 'Cancelled', 'Pending', 'gaulan'),
(117, 0, 3, 1, NULL, 'customer2', 'customer2', '09765434212', 'labuyo', 'labuyo', 'labuyo', 'Cagayan', '2025-01-25', '17:00:00', '19:00:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 1 appearance', '', 2500, 1250, 1250, '../images/679158c3de4f5.jpg', 'Cancelled', 'Pending', 'sige daw'),
(118, 0, 1, 1, NULL, 'Customer', 'Customer', '09432336109', 'labuyo', 'labuyo', 'labuyo', 'Misamis Occidental', '2025-01-26', '17:00:00', '20:00:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 2 appearance', '', 3000, 1500, 1500, '../images/67926ef21791e.jpg', 'Cancelled', 'Pending', 'gagi'),
(119, 0, 1, 1, NULL, 'Customer', 'Customer', '09432336109', 'molave', 'molave', 'molave', 'Zamboanga del Sur', '2025-01-27', '17:45:00', '20:45:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 2 appearance', '', 3000, 1500, 1500, '../images/6792d4cf0d459.jpg', 'Declined', 'Pending', 'asa daw'),
(120, 0, 1, 1, NULL, 'Marc', 'Latonio', '09432336109', 'culo', 'culo', 'culo', 'Zamboanga del Sur', '2025-01-28', '16:14:00', '19:15:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 2 appearance', '', 3000, 1500, 1500, '../images/6792dbbe1b4ca.jpg', 'Pending', 'Pending', 'gagi par'),
(121, 0, 1, 1, NULL, 'Jamaica', 'Alone', '09432336109', 'bliss', 'culo', 'molave', 'Zamboanga del Sur', '2025-01-29', '17:00:00', '21:00:00', 'Markyyyy Latonio', 'Comedy Boxing,Comedy Clown,mascot', 'Comedy Boxing: 1 hour, Comedy Clown: 1 hour, mascot: 2 appearance', '', 3000, 1500, 1500, '../images/679925d2e8aa1.jpg', 'Approved', 'Pending', 'sige daw'),
(122, 0, 6, 1, NULL, 'G-ar', 'Delosa', '09766543442', 'labuyo', 'labuyo', 'tangub', 'Misamis Oriental', '2025-02-06', '08:00:00', '11:00:00', 'Markyyyy Latonio, entertainer2 entertainer2, Marc omandam', 'mascot,Comedy Clown,Fire Dance,Magician,Singer dancer', 'mascot: 1 appearance, Comedy Clown: 1 hour, Fire Dance: 1 hour, Magician: 1 hour, Singer dancer: 1 song', '', 3800, 1900, 1900, '../images/679c1e87726f4.PNG', 'Approved', 'Pending', 'aw');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking_report`
--
ALTER TABLE `booking_report`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `fk_customer` (`customer_id`),
  ADD KEY `fk_entertainer` (`entertainer_id`),
  ADD KEY `sched_id` (`sched_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking_report`
--
ALTER TABLE `booking_report`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

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
