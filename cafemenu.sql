-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 07:18 AM
-- Server version: 8.0.30
-- PHP Version: 7.4.30
CREATE DATABASE IF NOT EXISTS cafemenu;
USE cafemenu;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafemenu`
--

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` int DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `name`, `price`, `image`, `category`) VALUES
(1, 'Deep Roast Milk Tea', 35000, 'teh2.jpg', 'OUR SIGNATURE'),
(2, 'House Special Milk Tea', 42000, 'teh1.avif', 'OUR SIGNATURE'),
(3, 'Apple Green Tea', 43000, 'tehapel.jpg\r\n', 'OUR SIGNATURE'),
(5, 'Passion Fruit', 53000, 'passionfruit.jpg', 'OUR SIGNATURE'),
(6, 'Four Seasons Pure Tea', 23000, 'forsi.jpg', 'PURE TEA'),
(7, 'Deep Roast Oolong Pure Tea', 23000, 'dipros.jpg', 'PURE TEA'),
(8, 'Jasmine Green Pure Tea', 23000, 'jasmin.jpg', 'PURE TEA'),
(9, 'Pearl', 8000, 'boba.png', 'topping'),
(10, 'Soya Bean Curd', 8000, 'sbc.avif\r\n', 'topping'),
(11, 'Grass Jelly', 8000, 'cincau.jpg', 'topping'),
(12, 'Konjac Jelly', 12000, 'konjac.avif', 'topping'),
(13, 'Coconut Jelly', 8000, 'natadekoko.jpg', 'topping'),
(14, 'Tea Jelly', 8000, 'tijeli.jpg', 'topping'),
(15, 'Oats', 8000, 'oats.jpg', 'topping'),
(16, 'Honey Oolong Lemonade', 43000, 'honeyolonglemoned.avif', 'HONEY SERIES'),
(17, 'Honey Lemonade', 23000, 'honeylemonade.jpg', 'HONEY SERIES'),
(18, 'Deep Roast Oolong Milk Tea', 42000, 'diprosmilktea.jpg', 'MILK TEA'),
(20, 'Four Seasons Milk Tea', 32000, 'forsimilktea.avif', 'MILK TEA'),
(22, 'Jasmine Milk Tea', 32000, 'jasminmilktea.png', 'MILK TEA'),
(23, 'Honey Fragrant Tea', 35000, 'honeyfragrant.png', 'HONEY SERIES');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `table_no` varchar(10) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_whatsapp` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `total` int NOT NULL,
  `item` varchar(50) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `price` int DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
