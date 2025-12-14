-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 08:14 AM
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
-- Database: `wmsu_transport`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `AnnouncementID` int(11) NOT NULL,
  `Title` varchar(200) NOT NULL,
  `Content` text NOT NULL,
  `CreatedBy` int(11) NOT NULL,
  `PublishDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`AnnouncementID`, `Title`, `Content`, `CreatedBy`, `PublishDate`) VALUES
(1, 'notification test', 'hello world', 2, '2025-12-12 16:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `MessageID` int(11) NOT NULL,
  `FromUserID` int(11) NOT NULL,
  `ToUserID` int(11) NOT NULL,
  `Subject` varchar(255) DEFAULT NULL,
  `Body` text NOT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`MessageID`, `FromUserID`, `ToUserID`, `Subject`, `Body`, `IsRead`, `CreatedAt`) VALUES
(1, 4, 3, 'class project', 'hello world', 0, '2025-12-12 17:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `notificationreads`
--

CREATE TABLE `notificationreads` (
  `NotificationReadID` int(11) NOT NULL,
  `NotificationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ReadAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notificationreads`
--

INSERT INTO `notificationreads` (`NotificationReadID`, `NotificationID`, `UserID`, `ReadAt`) VALUES
(1, 1, 4, '2025-12-12 16:55:29'),
(2, 2, 4, '2025-12-12 17:37:05');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `Type` enum('Announcement','Schedule','Route','Stop') NOT NULL,
  `RelatedID` int(11) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `Type`, `RelatedID`, `Title`, `Message`, `IsRead`, `CreatedAt`) VALUES
(1, 'Announcement', 1, 'notification test', 'hello world', 0, '2025-12-12 16:55:08'),
(2, '', 1, 'Message: class project', 'You have received a new message from user ID 4', 0, '2025-12-12 17:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `passwordresets`
--

CREATE TABLE `passwordresets` (
  `ResetID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `Token` varchar(255) NOT NULL,
  `ExpiresAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Used` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `RouteID` int(11) NOT NULL,
  `RouteName` varchar(100) NOT NULL,
  `StartLocation` varchar(100) NOT NULL,
  `EndLocation` varchar(100) NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`RouteID`, `RouteName`, `StartLocation`, `EndLocation`, `IsActive`, `CreatedAt`) VALUES
(1, 'Main Campus Loop', 'WMSU Main Gate', 'WMSU Main Gate', 1, '2025-12-07 04:55:46'),
(2, 'City Route', 'WMSU Main Gate', 'City Hall', 1, '2025-12-07 04:55:46'),
(3, 'Normal Complex Route', 'WMSU Main Gate', 'Normal Complex', 1, '2025-12-07 04:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `routestops`
--

CREATE TABLE `routestops` (
  `RouteStopID` int(11) NOT NULL,
  `RouteID` int(11) NOT NULL,
  `StopID` int(11) NOT NULL,
  `StopOrder` int(11) NOT NULL,
  `ScheduledTime` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routestops`
--

INSERT INTO `routestops` (`RouteStopID`, `RouteID`, `StopID`, `StopOrder`, `ScheduledTime`) VALUES
(1, 1, 1, 1, '07:00:00'),
(2, 1, 2, 2, '07:10:00'),
(3, 1, 3, 3, '07:20:00'),
(4, 1, 4, 4, '07:30:00'),
(5, 2, 1, 1, '08:00:00'),
(6, 2, 5, 2, '08:20:00'),
(7, 2, 6, 3, '08:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `ScheduleID` int(11) NOT NULL,
  `RouteID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `DriverName` varchar(100) NOT NULL,
  `DateOfService` date NOT NULL,
  `Status` enum('On Time','Delayed','Canceled') DEFAULT 'On Time',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stops`
--

CREATE TABLE `stops` (
  `StopID` int(11) NOT NULL,
  `StopName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Latitude` decimal(10,8) DEFAULT NULL,
  `Longitude` decimal(11,8) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stops`
--

INSERT INTO `stops` (`StopID`, `StopName`, `Description`, `Latitude`, `Longitude`, `CreatedAt`) VALUES
(1, 'Main Gate', 'WMSU Main Entrance Gate', NULL, NULL, '2025-12-07 04:55:46'),
(2, 'CLA Building', 'College of Liberal Arts', NULL, NULL, '2025-12-07 04:55:46'),
(3, 'Engineering', 'College of Engineering', NULL, NULL, '2025-12-07 04:55:46'),
(4, 'Normal Hall', 'College of Education', NULL, NULL, '2025-12-07 04:55:46'),
(5, 'City Hall Stop', 'In front of City Hall', NULL, NULL, '2025-12-07 04:55:46'),
(6, 'Grandstand', 'City Grandstand Area', NULL, NULL, '2025-12-07 04:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `userregistrations`
--

CREATE TABLE `userregistrations` (
  `RegistrationID` int(11) NOT NULL,
  `WMSUID` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `UserType` enum('Student','Staff') DEFAULT 'Student',
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userregistrations`
--

INSERT INTO `userregistrations` (`RegistrationID`, `WMSUID`, `FirstName`, `LastName`, `Email`, `PasswordHash`, `UserType`, `Status`, `CreatedAt`) VALUES
(1, '20250002', 'Gian Cody', 'Salilig', 'sgiancody@gmail.com', '$2y$10$VcYMVNo/.zkuHQXi936JuOnULVHplQyYObSJQszJNCKUsQKKYYBEW', 'Student', 'Approved', '2025-12-07 05:41:40'),
(2, '20250004', 'Gian Cody', 'Salilig', 'giancody27@gmail.com', '$2y$10$n9R888ajgvI5qKL89tFqNOtyejnyWJJxJaxsp1zj6ac.mnVEzapXK', 'Student', 'Approved', '2025-12-07 05:42:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `WMSUID` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `UserType` enum('Admin','Student','Staff') NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `WMSUID`, `FirstName`, `LastName`, `Email`, `PasswordHash`, `UserType`, `CreatedAt`) VALUES
(1, 'admin2025', 'Admin', 'User', '', '$2y$10$/IqOmLKbKhvD.hMKAYdMh.F1HQ0iPILjz9V774/Fwr4ad4n4u9Zcy', 'Admin', '2025-12-07 05:24:49'),
(2, 'admin2024', 'Gian Cody', 'Salilig', 'nyannyaww@gmail.com', '$2y$10$924NwnOeN.jbm0mvcR5Va.1J23jDLayboaUnrCXuNRUftOR6wVl8m', 'Admin', '2025-12-07 05:36:54'),
(3, '20250002', 'Gian Cody', 'Salilig', 'sgiancody@gmail.com', '$2y$10$VcYMVNo/.zkuHQXi936JuOnULVHplQyYObSJQszJNCKUsQKKYYBEW', 'Student', '2025-12-07 05:43:16'),
(4, '20250004', 'Gian Cody', 'Salilig', 'giancody27@gmail.com', '$2y$10$n9R888ajgvI5qKL89tFqNOtyejnyWJJxJaxsp1zj6ac.mnVEzapXK', 'Student', '2025-12-07 05:43:22'),
(5, 'admin2023', 'omsim', 'wow', 'hz202301379@wmsu.edu.ph', '$2y$10$Ug8uMoAHnZDfxwkfo/ZUCepwaXtSFxvdEj8IWr0K.1B4mjmaL3tfm', 'Admin', '2025-12-13 13:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `VehicleID` int(11) NOT NULL,
  `PlateNumber` varchar(20) NOT NULL,
  `VehicleType` enum('WMSU Bus','Van','Jeepney') NOT NULL,
  `Status` enum('Operational','Maintenance','Out of Service') DEFAULT 'Operational'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`AnnouncementID`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`MessageID`),
  ADD KEY `FromUserID` (`FromUserID`),
  ADD KEY `ToUserID` (`ToUserID`);

--
-- Indexes for table `notificationreads`
--
ALTER TABLE `notificationreads`
  ADD PRIMARY KEY (`NotificationReadID`),
  ADD KEY `NotificationID` (`NotificationID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`);

--
-- Indexes for table `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD PRIMARY KEY (`ResetID`),
  ADD UNIQUE KEY `Token` (`Token`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`RouteID`);

--
-- Indexes for table `routestops`
--
ALTER TABLE `routestops`
  ADD PRIMARY KEY (`RouteStopID`),
  ADD KEY `RouteID` (`RouteID`),
  ADD KEY `StopID` (`StopID`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `RouteID` (`RouteID`),
  ADD KEY `VehicleID` (`VehicleID`);

--
-- Indexes for table `stops`
--
ALTER TABLE `stops`
  ADD PRIMARY KEY (`StopID`);

--
-- Indexes for table `userregistrations`
--
ALTER TABLE `userregistrations`
  ADD PRIMARY KEY (`RegistrationID`),
  ADD UNIQUE KEY `WMSUID` (`WMSUID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `WMSUID` (`WMSUID`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`VehicleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `AnnouncementID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notificationreads`
--
ALTER TABLE `notificationreads`
  MODIFY `NotificationReadID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `passwordresets`
--
ALTER TABLE `passwordresets`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `RouteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `routestops`
--
ALTER TABLE `routestops`
  MODIFY `RouteStopID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `ScheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stops`
--
ALTER TABLE `stops`
  MODIFY `StopID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `userregistrations`
--
ALTER TABLE `userregistrations`
  MODIFY `RegistrationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `VehicleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`FromUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`ToUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `notificationreads`
--
ALTER TABLE `notificationreads`
  ADD CONSTRAINT `notificationreads_ibfk_1` FOREIGN KEY (`NotificationID`) REFERENCES `notifications` (`NotificationID`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificationreads_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD CONSTRAINT `passwordresets_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `routestops`
--
ALTER TABLE `routestops`
  ADD CONSTRAINT `routestops_ibfk_1` FOREIGN KEY (`RouteID`) REFERENCES `routes` (`RouteID`),
  ADD CONSTRAINT `routestops_ibfk_2` FOREIGN KEY (`StopID`) REFERENCES `stops` (`StopID`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`RouteID`) REFERENCES `routes` (`RouteID`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
