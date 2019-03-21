-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Mar 21, 2019 at 01:11 AM
-- Server version: 5.7.23
-- PHP Version: 7.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `email_simulator`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `subject`, `message`, `date`) VALUES
(6, 2, 'no soy uno', '<p>no no</p>', '2018-05-05 00:00:00'),
(11, 1, 'test db', '<p>lala</p>', '2018-02-02 00:00:00'),
(39, 1, 'hola admin', '<p>hola admin esto es un test</p>\n', '2018-11-22 02:19:05'),
(40, 1, 'send the cops dude', '<p>Hola John,</p>\n<p>Tenemos un <span style=\"color: rgb(209,72,65);font-size: 24px;\"><strong>PROBLEMA</strong></span> muy grave...</p>\n<p>El edificio esta tomado!!</p>\n<p>llama a la policia para que nos traiga refuerzos.</p>\n<p>Saludos,</p>\n<p><em>Juan Ignacio Alterio</em></p>\n', '2018-11-22 03:57:30'),
(41, 1, 'Hola', '<p>😀</p>\n', '2018-11-22 13:29:23'),
(42, 1, '', '<p>😀</p>\n', '2018-11-22 13:30:22'),
(43, 1, '', '<p style=\"text-align:center;\"><span style=\"color: rgb(0,168,133);font-size: 96px;\"><strong><em><ins>HOLA</ins></em></strong></span><span style=\"font-size: 96px;\"><strong><em><ins> </ins></em></strong></span><span style=\"color: rgb(251,160,38);font-size: 96px;\"><strong><em><ins>BUENAS</ins></em></strong></span></p>\n<ul>\n<li>hola</li>\n<li>soy&nbsp;</li>\n<li>lista</li>\n</ul>\n<img src=\"http://www.hello.com/img_/hellowithwaves.png\" alt=\"undefined\" style=\"float:none;height: auto;width: auto\"/>\n<p></p>\n', '2018-11-22 13:41:43'),
(44, 1, 'kkk', '<p>kkk</p>\n', '2018-11-22 13:44:42'),
(45, 1, 'Hola soy vos', '<p>Soy Juani...</p>\n<p>test 123 🤘</p>\n<h5><span style=\"font-size: 72px;font-family: Impact;\">PROBANDO </span><span style=\"color: rgb(65,168,95);font-size: 72px;font-family: Impact;\">HTML</span></h5>\n<p>😜</p>\n', '2018-11-22 21:52:04'),
(46, 1, 'Hola John', '<p>HI from Mar del plata</p>\n', '2018-11-22 22:02:08'),
(47, 1, '123', 'null', '2018-11-22 22:02:27'),
(48, 1, '', '<p><span style=\"font-size: 72px;\">TEST</span><span style=\"color: rgba(0,0,0,0.87);background-color: rgb(255,255,255);font-size: 72px;font-family: -apple-system, system-ui, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji;\">TEST</span></p>\n<p><span style=\"color: rgba(0,0,0,0.87);background-color: rgb(255,255,255);font-size: 72px;font-family: -apple-system, system-ui, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji;\">TESTTEST</span></p>\n<p><span style=\"color: rgba(0,0,0,0.87);background-color: rgb(255,255,255);font-size: 72px;font-family: -apple-system, system-ui, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji;\">TESTTEST</span></p>\n<p><span style=\"color: rgba(0,0,0,0.87);background-color: rgb(255,255,255);font-size: 72px;font-family: -apple-system, system-ui, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji;\">TESTTEST</span></p>\n', '2018-11-22 22:02:59'),
(49, 1, '', '', '2018-11-22 22:09:46'),
(50, 1, '', '', '2018-11-22 22:12:04'),
(51, 1, 'hola me', '<p>me me me</p>\n', '2018-11-22 22:26:49'),
(52, 1, 'me2', '<p>22</p>\n', '2018-11-22 22:27:20'),
(53, 1, 'hola test prueba', '<p>hola&nbsp;</p>\n<p>😛</p>\n<h6><span style=\"font-size: 96px;font-family: Impact;\">hol</span><span style=\"color: rgb(235,107,86);font-size: 96px;font-family: Impact;\">a</span></h6>\n', '2018-11-22 22:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
--

CREATE TABLE `recipients` (
  `recipient_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `recipients`
--

INSERT INTO `recipients` (`recipient_id`, `message_id`) VALUES
(1, 6),
(2, 39),
(3, 40),
(3, 41),
(1, 42),
(3, 43),
(1, 44),
(1, 45),
(3, 46),
(3, 47),
(3, 48),
(3, 49),
(3, 50),
(1, 51),
(1, 52),
(1, 53);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `firstname` varchar(45) DEFAULT NULL,
  `lastname` varchar(45) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `firstname`, `lastname`, `password`) VALUES
(1, 'juanialt', 'Juan Ignacio', 'Alterio', '81DC9BDB52D04DC20036DBD8313ED055'),
(2, 'admin', 'Jon', 'Doe', 'F1C1592588411002AF340CBAEDD6FC33'),
(3, 'jmcclane', 'John', 'McClane', 'f1c1592588411002af340cbaedd6fc33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_sender_id_idx` (`sender_id`);

--
-- Indexes for table `recipients`
--
ALTER TABLE `recipients`
  ADD PRIMARY KEY (`recipient_id`,`message_id`),
  ADD KEY `fk_message_id_idx` (`message_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username_UNIQUE` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_sender_id` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `recipients`
--
ALTER TABLE `recipients`
  ADD CONSTRAINT `fk_message_id` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_recipient_id` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
