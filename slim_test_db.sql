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
-- Database: `slim_test_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `completed_transactions`
--

CREATE TABLE `completed_transactions` (
  `trans_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,
  `cust_id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shift` int(1) DEFAULT NULL,
  `fuel` varchar(11) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `liters` decimal(5,2) NOT NULL DEFAULT 0.00,
  `trans_qr` varchar(16) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  `time_created` int(11) DEFAULT NULL,
  `trans_time` varchar(11) DEFAULT NULL,
  `video` char(1) NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `completed_transactions`
--

INSERT INTO `completed_transactions` (`trans_id`, `pump_id`, `cust_id`, `car_id`, `user_id`, `shift`, `fuel`, `amount`, `rate`, `liters`, `trans_qr`, `date`, `last_updated`, `time_created`, `trans_time`, `video`) VALUES
(14, 1, 1, 0, 1, 2, 'petrol', '1600.00', '80.00', '20.00', 'lgurT6xW72', '2020-08-09 22:46:40', NULL, 1597252600, NULL, 'N');

-- --------------------------------------------------------

--
-- Table structure for table `otp_request`
--

CREATE TABLE `otp_request` (
  `otp_req_id` int(11) NOT NULL,
  `otp_req_ph_no` varchar(15) NOT NULL,
  `otp_req_otp` varchar(7) NOT NULL,
  `otp_req_timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `otp_request`
--

INSERT INTO `otp_request` (`otp_req_id`, `otp_req_ph_no`, `otp_req_otp`, `otp_req_timestamp`) VALUES
(6, '8411815106', '5017', 1597246723);

-- --------------------------------------------------------

--
-- Table structure for table `pending_transactions`
--

CREATE TABLE `pending_transactions` (
  `pend_trans_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `car_qr` varchar(255) DEFAULT NULL,
  `trans_qr` varchar(255) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `fuel_type` varchar(10) DEFAULT NULL,
  `payment_status` varchar(15) DEFAULT NULL,
  `time_created` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(70) DEFAULT NULL,
  `user_ph_no` varchar(20) NOT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `firebase_token` varchar(255) DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_ph_no`, `user_email`, `refresh_token`, `firebase_token`, `date_created`, `last_updated`) VALUES
(1, 'sourabh jigjinni', '8411815106', 'sourabhjigjinni@gmail.com', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLm9yZyIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUuY29tIiwiaWF0IjoxNTk3MjQ2NzI4LCJleHAiOjE2Mjg3ODI3MjgsImlkIjoiMSJ9.oYImbFXDMvWgO9cAGuImGQSf0oLbO9yFog-gGmEBAmo', 'e5gT2lFvS0iNM8B1CvPdQ_:APA91bHxMv7lci1Cl75shDSXbEmAR_8KvYK4aEx2V_mU-VyTbskLbXGbq-ySJkeexhXoMRJw-gdE4ljlqHu2q66L4siCLW9EVXFenW84x1iWJhS4-RDDmSSM3F4uk6ZogjYDilBytRud', NULL, NULL),
(2, NULL, '9762230207', NULL, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLm9yZyIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUuY29tIiwiaWF0IjoxNTk2NDM5Njc3LCJleHAiOjE2Mjc5NzU2NzcsImlkIjoiMiJ9.JSuK95RiDJ5YPrST3RKW_FT-gbbO1RqXq4Y9KDpHhfs', NULL, 1596439677, 1596439677);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `completed_transactions`
--
ALTER TABLE `completed_transactions`
  ADD PRIMARY KEY (`trans_id`);

--
-- Indexes for table `otp_request`
--
ALTER TABLE `otp_request`
  ADD PRIMARY KEY (`otp_req_id`);

--
-- Indexes for table `pending_transactions`
--
ALTER TABLE `pending_transactions`
  ADD PRIMARY KEY (`pend_trans_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `completed_transactions`
--
ALTER TABLE `completed_transactions`
  MODIFY `trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `otp_request`
--
ALTER TABLE `otp_request`
  MODIFY `otp_req_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pending_transactions`
--
ALTER TABLE `pending_transactions`
  MODIFY `pend_trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
