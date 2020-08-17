-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 17, 2020 at 12:11 PM
-- Server version: 10.4.13-MariaDB
-- PHP Version: 7.4.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `local_petrol_pump`
--

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `cam_id` int(1) NOT NULL,
  `cam_no` int(2) NOT NULL,
  `cam_qr_code` varchar(11) NOT NULL,
  `status` int(1) NOT NULL,
  `type` varchar(5) DEFAULT NULL,
  `trans_string` varchar(11) DEFAULT NULL,
  `cust_type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cameras`
--

INSERT INTO `cameras` (`cam_id`, `cam_no`, `cam_qr_code`, `status`, `type`, `trans_string`, `cust_type`) VALUES
(1, 1, '8FuAVN303E', 1, 'stop', 'lgurT6xW72', NULL),
(2, 2, '4xzliayQPL', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `trans_id` int(11) NOT NULL,
  `transaction_no` varchar(30) DEFAULT NULL,
  `pump_id` int(11) NOT NULL,
  `cust_id` int(11) NOT NULL,
  `cust_type` varchar(10) DEFAULT NULL,
  `car_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `receipt_no` int(11) DEFAULT NULL,
  `shift` int(1) NOT NULL DEFAULT 1,
  `fuel` varchar(11) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `liters` decimal(5,2) NOT NULL DEFAULT 0.00,
  `billed` char(1) NOT NULL DEFAULT 'N',
  `trans_string` varchar(11) DEFAULT NULL,
  `date` datetime NOT NULL,
  `last_updated` datetime NOT NULL,
  `trans_time` varchar(11) DEFAULT NULL,
  `uploaded` char(1) NOT NULL DEFAULT 'N',
  `video` char(1) NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`trans_id`, `transaction_no`, `pump_id`, `cust_id`, `cust_type`, `car_id`, `user_id`, `receipt_no`, `shift`, `fuel`, `amount`, `rate`, `liters`, `billed`, `trans_string`, `date`, `last_updated`, `trans_time`, `uploaded`, `video`) VALUES
(29, NULL, 1, 0, 'online', 0, 1, NULL, 2, 'petrol', '1600.00', '80.00', '20.00', 'N', 'lgurT6xW72', '2020-08-09 22:51:48', '2020-08-12 22:51:48', NULL, 'N', 'Y');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`cam_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`trans_id`),
  ADD KEY `trans_string` (`trans_string`),
  ADD KEY `date` (`date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `cam_id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
