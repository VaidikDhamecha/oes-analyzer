-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2026 at 06:50 AM
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
-- Database: `oes`
--

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `exam_title` varchar(255) NOT NULL,
  `total_questions` int(10) NOT NULL,
  `duration_mins` int(11) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_title`, `total_questions`, `duration_mins`, `duration`, `status`) VALUES
(4, 'CIA 2', 0, 45, 10, 'Active'),
(7, 'CIA', 0, NULL, 20, 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_text` text DEFAULT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_option` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(6, 4, 'FULL FORM OF PHP', 'HYPERTEXT PRE PROCESSORR', 'HYPERTEXT POST PROCESSOR', 'HYPERTEXT PRE PROCESSORS', 'HYPERTEXT PROCESSOR', 'A'),
(7, 4, 'Which of the following is the correct way to start a PHP block?', '<?php', '<php!>', '<?', '<script php>', 'A'),
(8, 4, 'Who is known as the father of PHP?', 'Rasmus Lerdorf', 'Willam Makepiece', 'Drek Kolkevi', 'Guido van Rossum', 'A'),
(9, 4, 'Which of the following is the correct way to start a PHP script?', '<php>', '<?php', '<?', '<script>', 'B'),
(10, 4, 'In PHP, variable names must start with which symbol?', '#', '&', '$', '!', 'C'),
(11, 4, 'Which function is used to return the length of a string in PHP?', 'strlen()', 'strlength()', 'length()', 'count()', 'A'),
(14, 4, 'FULL FORM OF PHP', 'HYPERTEXT PRE PROCESSORR', 'HYPERTEXT POST PROCESSOR', 'HYPERTEXT PRE PROCESSORS', 'HYPERTEXT PROCESSOR', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `answers_json` text DEFAULT NULL,
  `score` int(11) NOT NULL,
  `time_spent_minutes` int(11) DEFAULT 0,
  `date_taken` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'in-progress',
  `time_spent` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_name`, `user_id`, `exam_id`, `answers_json`, `score`, `time_spent_minutes`, `date_taken`, `status`, `time_spent`) VALUES
(42, NULL, 9, 4, '{\"6\":\"A\",\"7\":\"A\",\"8\":\"A\",\"9\":\"\",\"10\":\"A\",\"11\":\"A\",\"14\":\"A\"}', 5, 0, '2026-05-23 10:29:37', 'in-progress', 0),
(75, NULL, 8, 4, NULL, 71, 0, '2026-05-28 10:30:50', 'completed', 0),
(77, NULL, 8, 4, NULL, 71, 0, '2026-05-28 10:38:31', 'completed', 0),
(78, NULL, 8, 4, NULL, 86, 0, '2026-05-28 10:39:55', 'completed', 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `selected_option` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `user_id`, `exam_id`, `question_id`, `result_id`, `selected_option`) VALUES
(1, 6, 4, 6, 28, '1'),
(2, 6, 4, 7, 28, '1'),
(3, 6, 4, 8, 28, '1'),
(4, 6, 4, 9, 28, '1'),
(5, 6, 4, 10, 28, '1'),
(6, 6, 4, 11, 28, '1'),
(7, 6, 4, 6, 29, '1'),
(8, 6, 4, 7, 29, '1'),
(9, 6, 4, 8, 29, '1'),
(10, 6, 4, 9, 29, '1'),
(11, 6, 4, 10, 29, '1'),
(12, 6, 4, 11, 29, '1'),
(13, 6, 4, 6, 30, '1'),
(14, 6, 4, 7, 30, '1'),
(15, 6, 4, 8, 30, '1'),
(16, 6, 4, 9, 30, '1'),
(17, 6, 4, 10, 30, '2'),
(18, 6, 4, 11, 30, '1'),
(19, 6, 4, 6, 31, '1'),
(20, 6, 4, 7, 31, '1'),
(21, 6, 4, 8, 31, '1'),
(22, 6, 4, 9, 31, '1'),
(23, 6, 4, 10, 31, '1'),
(24, 6, 4, 11, 31, '1'),
(25, 8, 4, 6, 49, 'A'),
(26, 8, 4, 7, 49, 'A'),
(27, 8, 4, 8, 49, 'B'),
(28, 8, 4, 9, 49, 'B'),
(29, 8, 4, 10, 49, 'B'),
(30, 8, 4, 11, 49, 'C'),
(31, 8, 4, 14, 49, 'B'),
(32, 8, 4, 6, 50, 'A'),
(33, 8, 4, 7, 50, 'A'),
(34, 8, 4, 8, 50, 'A'),
(35, 8, 4, 9, 50, 'A'),
(36, 8, 4, 10, 50, 'A'),
(37, 8, 4, 11, 50, 'A'),
(38, 8, 4, 14, 50, 'A'),
(39, 8, 4, 6, 52, 'A'),
(40, 8, 4, 7, 52, 'A'),
(41, 8, 4, 8, 52, 'A'),
(42, 8, 4, 10, 52, 'A'),
(43, 8, 4, 11, 52, 'B'),
(44, 8, 4, 14, 52, 'A'),
(45, 8, 4, 6, 53, 'A'),
(46, 8, 4, 7, 53, 'A'),
(47, 8, 4, 8, 53, 'A'),
(48, 8, 4, 9, 53, 'A'),
(49, 8, 4, 10, 53, 'C'),
(50, 8, 4, 11, 53, 'A'),
(51, 8, 4, 14, 53, 'A'),
(52, 8, 4, 6, 54, 'A'),
(53, 8, 4, 7, 54, 'A'),
(54, 8, 4, 8, 54, 'A'),
(55, 8, 4, 9, 54, 'A'),
(56, 8, 4, 10, 54, 'B'),
(57, 8, 4, 11, 54, 'B'),
(58, 8, 4, 14, 54, 'A'),
(59, 8, 4, 6, 55, 'A'),
(60, 8, 4, 7, 55, 'A'),
(61, 8, 4, 8, 55, 'A'),
(62, 8, 4, 9, 55, 'A'),
(63, 8, 4, 10, 55, 'A'),
(64, 8, 4, 11, 55, 'A'),
(65, 8, 4, 14, 55, 'A'),
(66, 8, 4, 6, 56, 'A'),
(67, 8, 4, 7, 56, 'A'),
(68, 8, 4, 8, 56, 'A'),
(69, 8, 4, 9, 56, 'A'),
(70, 8, 4, 10, 56, 'A'),
(71, 8, 4, 11, 56, 'A'),
(72, 8, 4, 14, 56, 'A'),
(73, 8, 4, 6, 57, 'A'),
(74, 8, 4, 7, 57, 'A'),
(75, 8, 4, 8, 57, 'B'),
(76, 8, 4, 9, 57, 'A'),
(77, 8, 4, 10, 57, 'B'),
(78, 8, 4, 11, 57, 'C'),
(79, 8, 4, 14, 57, 'B'),
(80, 8, 4, 6, 58, 'A'),
(81, 8, 4, 7, 58, 'A'),
(82, 8, 4, 8, 58, 'A'),
(83, 8, 4, 9, 58, 'A'),
(84, 8, 4, 10, 58, 'B'),
(85, 8, 4, 11, 58, 'C'),
(86, 8, 4, 14, 58, 'B'),
(87, 8, 4, 6, 59, 'A'),
(88, 8, 4, 7, 59, 'A'),
(89, 8, 4, 8, 59, 'A'),
(90, 8, 4, 9, 59, 'A'),
(91, 8, 4, 10, 59, 'B'),
(92, 8, 4, 11, 59, 'C'),
(93, 8, 4, 14, 59, 'A'),
(94, 8, 4, 6, 60, 'A'),
(95, 8, 4, 7, 60, 'A'),
(96, 8, 4, 8, 60, 'A'),
(97, 8, 4, 9, 60, 'A'),
(98, 8, 4, 10, 60, 'A'),
(99, 8, 4, 11, 60, 'A'),
(100, 8, 4, 14, 60, 'A'),
(101, 8, 4, 6, 61, 'A'),
(102, 8, 4, 7, 61, 'A'),
(103, 8, 4, 8, 61, 'A'),
(104, 8, 4, 9, 61, 'A'),
(105, 8, 4, 10, 61, 'A'),
(106, 8, 4, 11, 61, 'B'),
(107, 8, 4, 14, 61, 'A'),
(108, 8, 4, 6, 62, 'A'),
(109, 8, 4, 7, 62, 'A'),
(110, 8, 4, 8, 62, 'A'),
(111, 8, 4, 9, 62, 'A'),
(112, 8, 4, 10, 62, 'A'),
(113, 8, 4, 11, 62, 'C'),
(114, 8, 4, 14, 62, 'B'),
(115, 8, 4, 6, 63, 'B'),
(116, 8, 4, 7, 63, 'D'),
(117, 8, 4, 8, 63, 'B'),
(118, 8, 4, 9, 63, 'C'),
(119, 8, 4, 10, 63, 'B'),
(120, 8, 4, 11, 63, 'B'),
(121, 8, 4, 14, 63, 'B'),
(122, 8, 4, 6, 64, 'A'),
(123, 8, 4, 7, 64, 'B'),
(124, 8, 4, 8, 64, 'C'),
(125, 8, 4, 9, 64, 'B'),
(126, 8, 4, 10, 64, 'B'),
(127, 8, 4, 11, 64, 'A'),
(128, 8, 4, 14, 64, 'B'),
(129, 8, 4, 6, 65, 'A'),
(130, 8, 4, 7, 65, 'A'),
(131, 8, 4, 8, 65, 'A'),
(132, 8, 4, 9, 65, 'B'),
(133, 8, 4, 10, 65, 'C'),
(134, 8, 4, 11, 65, 'B'),
(135, 8, 4, 14, 65, 'A'),
(136, 8, 4, 6, 66, 'A'),
(137, 8, 4, 7, 66, 'A'),
(138, 8, 4, 8, 66, 'B'),
(139, 8, 4, 9, 66, 'A'),
(140, 8, 4, 10, 66, 'B'),
(141, 8, 4, 11, 66, 'C'),
(142, 8, 4, 14, 66, 'C'),
(143, 8, 4, 6, 75, 'A'),
(144, 8, 4, 7, 75, 'A'),
(145, 8, 4, 8, 75, 'A'),
(146, 8, 4, 9, 75, 'B'),
(147, 8, 4, 10, 75, 'B'),
(148, 8, 4, 11, 75, 'A'),
(149, 8, 4, 14, 75, 'C'),
(150, 8, 4, 6, 77, 'A'),
(151, 8, 4, 7, 77, 'A'),
(152, 8, 4, 8, 77, 'A'),
(153, 8, 4, 9, 77, 'A'),
(154, 8, 4, 10, 77, 'A'),
(155, 8, 4, 11, 77, 'A'),
(156, 8, 4, 14, 77, 'A'),
(157, 8, 4, 6, 78, 'A'),
(158, 8, 4, 7, 78, 'A'),
(159, 8, 4, 8, 78, 'A'),
(160, 8, 4, 9, 78, 'A'),
(161, 8, 4, 10, 78, 'C'),
(162, 8, 4, 11, 78, 'A'),
(163, 8, 4, 14, 78, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `profile_pic`, `password`, `role`) VALUES
(1, 'sofiaa', '1777697950_soffee4-removebg-preview.png', '$2y$10$tpXCa34UmNXjxX5YRPXmdeDo/UBPkBDnZAGHuyeq1G2PmBNI32lLK', 'admin'),
(2, 'vaidik', 'avatar_2_1780028818.jpg', '$2y$10$BkcGeS08ZpweMUt7EfucWeUFYBi0cSpDwWeeMXTg1MI3FYRjjxdR.', 'admin'),
(3, '', '1778561896_soffee3.PNG', '$2y$10$k02bkczH4BomGBYvdHweLOtakARu0lbhrWh0JVZdOkWJTsES9Waci', 'student'),
(4, '', NULL, '$2y$10$kbiiwKfP7je4a74lmyvxcOf7agv3j6ExohHaSos4dyufaTBoR8aGC', 'student'),
(5, '', NULL, '$2y$10$3C9h82caWJVtwRScV1JMcuaG5hxfu45.kqPecZ39rNMU3unmaaY6K', 'student'),
(6, 'vaidik', 'uploads/1778904579_soffee4.PNG', '$2y$10$yscuSGTL1uinONeNRpvVOu4SCM1cx7t4me/S5A7ZLnPxKaKTRwaHi', 'student'),
(7, 'vaidik', NULL, '$2y$10$noqJwEzSXjCpa.EMKkbDfOnEw5Dsi/tZeZumSGN4zqJaP1SoUadsa', 'student'),
(8, 'sofia', 'uploads/1780030268_soffee4-removebg-preview.png', '$2y$10$zwW9BYmIkPyTIGHZPQiXGOoLZ8NyqXuJ.9K9p9XzYsDe8SqXYTHp2', 'student'),
(9, 'Manav', 'uploads/1779512353_soffee3.PNG', '$2y$10$t0WCKiZazGYeTuEbn0r4eeHawl/NJ28Y98cJlJXT7mkJN6hivb0LK', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `user_answers`
--

CREATE TABLE `user_answers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `result_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `selected_option` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_answers`
--

INSERT INTO `user_answers` (`id`, `user_id`, `exam_id`, `result_id`, `question_id`, `selected_option`, `is_correct`) VALUES
(1, 3, 4, NULL, 6, 'a', 0),
(2, 3, 4, NULL, 7, 'a', 0),
(3, 3, 4, NULL, 8, 'a', 0),
(4, 3, 4, NULL, 9, 'b', 0),
(5, 3, 4, NULL, 10, 'c', 0),
(6, 3, 4, NULL, 11, 'a', 0),
(7, 3, 4, NULL, 6, 'a', 0),
(8, 3, 4, NULL, 7, 'a', 0),
(9, 3, 4, NULL, 8, 'a', 0),
(10, 3, 4, NULL, 9, 'b', 0),
(11, 3, 4, NULL, 10, 'c', 0),
(12, 3, 4, NULL, 11, 'a', 0),
(13, 3, 4, NULL, 6, 'b', 0),
(14, 3, 4, NULL, 7, 'a', 0),
(15, 3, 4, NULL, 8, 'b', 0),
(16, 3, 4, NULL, 9, 'a', 0),
(17, 3, 4, NULL, 10, 'c', 0),
(18, 3, 4, NULL, 11, 'a', 0),
(19, 3, 4, 15, 6, 'a', 1),
(20, 3, 4, 15, 7, 'a', 1),
(21, 3, 4, 15, 8, 'b', 0),
(22, 3, 4, 15, 9, 'b', 1),
(23, 3, 4, 15, 10, 'c', 1),
(24, 3, 4, 15, 11, 'a', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_answers`
--
ALTER TABLE `user_answers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_answers`
--
ALTER TABLE `user_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
