-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2025 at 12:58 AM
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
-- Table structure for table `combo_package_roles`
--

CREATE TABLE `combo_package_roles` (
  `id` int(11) NOT NULL,
  `combo_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `combo_package_roles`
--

INSERT INTO `combo_package_roles` (`id`, `combo_id`, `role_id`) VALUES
(13, 1, 7),
(14, 1, 14),
(15, 1, 9),
(16, 5, 7),
(17, 5, 9),
(18, 5, 14);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `combo_package_roles`
--
ALTER TABLE `combo_package_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `combo_id` (`combo_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `combo_package_roles`
--
ALTER TABLE `combo_package_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `combo_package_roles`
--
ALTER TABLE `combo_package_roles`
  ADD CONSTRAINT `combo_package_roles_ibfk_1` FOREIGN KEY (`combo_id`) REFERENCES `combo_packages` (`combo_id`),
  ADD CONSTRAINT `combo_package_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
