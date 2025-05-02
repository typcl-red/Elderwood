-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2025 at 09:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elderwood`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `firstname`, `lastname`, `email`, `created_at`) VALUES
(6, 'admin', '$2y$10$GaPDVrXpcKZ6wrA2iTukr.G9SnXM5zmGhNcuavl2xBkwuxa8VjK9u', 'Admin', 'User', 'admin@elderwood.com', '2025-04-25 05:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `cus_orders`
--

CREATE TABLE `cus_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `length_feet` decimal(10,2) DEFAULT NULL,
  `width_feet` decimal(10,2) DEFAULT NULL,
  `height_feet` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price_per_sqft` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cus_orders`
--

INSERT INTO `cus_orders` (`id`, `order_id`, `product_name`, `length_feet`, `width_feet`, `height_feet`, `quantity`, `price_per_sqft`, `total_amount`, `created_at`) VALUES
(30, 36, 'Coconut', 2.00, 3.00, 8.00, 8, 25.00, 200.00, '2025-04-24 01:53:34'),
(31, 37, 'Coconut', 2.00, 3.00, 8.00, 10, 25.00, 250.00, '2025-04-24 02:05:31'),
(32, 38, 'Coconut', 2.00, 3.00, 8.00, 7, 25.00, 175.00, '2025-04-24 02:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales`
--

CREATE TABLE `daily_sales` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `seller_name` varchar(255) NOT NULL,
  `sale_date` date NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `length_feet` decimal(10,2) NOT NULL,
  `width_feet` decimal(10,2) NOT NULL,
  `height_feet` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_sales`
--

INSERT INTO `daily_sales` (`id`, `seller_id`, `seller_name`, `sale_date`, `product_name`, `length_feet`, `width_feet`, `height_feet`, `quantity`, `total_amount`, `created_at`) VALUES
(5, 1, 'Carl Jevan Deala', '2025-04-24', 'Coconut', 2.00, 3.00, 8.00, 8, 200.00, '2025-04-24 01:54:06'),
(6, 1, 'Carl Jevan Deala', '2025-04-24', 'Coconut', 2.00, 3.00, 8.00, 10, 250.00, '2025-04-24 02:06:29'),
(7, 1, 'Carl Jevan Deala', '2025-04-24', 'Coconut', 2.00, 3.00, 8.00, 7, 175.00, '2025-04-24 02:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_schedules`
--

CREATE TABLE `delivery_schedules` (
  `id` int(11) NOT NULL,
  `presentation_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Declined','Completed','On The Way','Arrived','Scheduled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_schedules`
--

INSERT INTO `delivery_schedules` (`id`, `presentation_id`, `seller_id`, `supplier_id`, `scheduled_date`, `created_at`, `status`) VALUES
(15, 23, 1, 5, '2025-04-23 19:12:00', '2025-04-21 07:01:04', 'Arrived');

-- --------------------------------------------------------

--
-- Table structure for table `for_pickup`
--

CREATE TABLE `for_pickup` (
  `pickup_id` int(11) NOT NULL,
  `buyer_name` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `length_feet` decimal(10,2) NOT NULL,
  `width_feet` decimal(10,2) NOT NULL,
  `height_feet` decimal(10,2) NOT NULL,
  `ready_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `for_pickup`
--

INSERT INTO `for_pickup` (`pickup_id`, `buyer_name`, `product_name`, `length_feet`, `width_feet`, `height_feet`, `ready_at`, `order_id`) VALUES
(5, 'Buyer Lumber', 'Coconut', 2.00, 3.00, 8.00, '2025-04-24 01:55:21', 36),
(6, 'Buyer Lumber', 'Coconut', 2.00, 3.00, 8.00, '2025-04-24 02:06:46', 37),
(7, 'Buyer Lumber', 'Coconut', 2.00, 3.00, 8.00, '2025-04-24 02:09:51', 38);

-- --------------------------------------------------------

--
-- Table structure for table `gcash`
--

CREATE TABLE `gcash` (
  `gcash_id` int(25) NOT NULL,
  `seller_id` int(25) NOT NULL,
  `gcash_name` varchar(100) DEFAULT NULL,
  `gcash_number` varchar(11) DEFAULT NULL,
  `gcash_qr` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gcash`
--

INSERT INTO `gcash` (`gcash_id`, `seller_id`, `gcash_name`, `gcash_number`, `gcash_qr`, `created_at`) VALUES
(4, 1, 'Carl Jevan Deala', '09654500757', 'uploads/gcash_qr/680b1ca4c3876_1745558692.jpg', '2025-03-26 02:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'In Stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `supplier_id`, `product_name`, `quantity`, `description`, `status`, `created_at`, `updated_at`, `length`, `width`, `height`) VALUES
(5, 5, 'Mahogany', 140, 'Good Wood', 'In Stock', '2025-04-15 03:12:20', '2025-04-15 03:12:20', 2.00, 5.00, 8.00),
(6, 5, 'Mangga', 150, 'Good wood', 'In Stock', '2025-04-15 04:24:37', '2025-04-15 04:24:37', 5.00, 5.00, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `grand_total_volume` decimal(10,2) NOT NULL,
  `total_bill` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `sent_to_supplier` tinyint(1) DEFAULT 0,
  `item_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `seller_id`, `supplier_id`, `product_name`, `price_per_unit`, `grand_total_volume`, `total_bill`, `created_at`, `payment_proof`, `payment_date`, `sent_to_supplier`, `item_id`) VALUES
(15, 1, 5, 'Mangga', 30.00, 2106.67, 63200.00, '2025-04-22 01:32:31', 'uploads/payments/1745285624_4510165a-ffaa-4641-b464-c88f94b59cd5.jpg', '2025-04-22 01:33:44', 1, 6);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `item_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `length_feet` decimal(10,2) NOT NULL,
  `width_feet` decimal(10,2) NOT NULL,
  `height_feet` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_volume` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`item_id`, `invoice_id`, `length_feet`, `width_feet`, `height_feet`, `quantity`, `total_volume`) VALUES
(43, 15, 5.00, 5.00, 8.00, 120, 2000.00),
(44, 15, 3.00, 3.00, 6.00, 20, 90.00),
(45, 15, 2.00, 2.00, 5.00, 10, 16.67);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `reference_id`, `is_read`, `created_at`) VALUES
(23, 1, 'order', 'New order received!\nProduct: Coconut\nSize: 2\' x 3\' x 8\'\nQuantity: 5 pieces', 32, 1, '2025-04-03 04:14:36'),
(24, 1, 'order', 'New order received!\nProduct: Mahogany\nSize: 8\' x 8\' x 8\'\nQuantity: 5 pieces', 33, 1, '2025-04-22 06:32:00'),
(25, 1, 'order', 'New order received!\nProduct: Mahogany\nSize: 8\' x 8\' x 8\'\nQuantity: 5 pieces', 34, 1, '2025-04-22 06:54:57'),
(26, 1, 'order', 'New order received!\nProduct: Coconut\nSize: 2\' x 3\' x 8\'\nQuantity: 15 pieces', 35, 1, '2025-04-24 01:46:20'),
(27, 1, 'order', 'New order received!\nProduct: Coconut\nSize: 2\' x 3\' x 8\'\nQuantity: 8 pieces', 36, 1, '2025-04-24 01:53:25'),
(28, 1, 'order', 'New order received!\nProduct: Coconut\nSize: 2\' x 3\' x 8\'\nQuantity: 10 pieces', 37, 1, '2025-04-24 02:05:25'),
(29, 1, 'order', 'New order received!\nProduct: Coconut\nSize: 2\' x 3\' x 8\'\nQuantity: 7 pieces', 38, 1, '2025-04-24 02:09:09');

-- --------------------------------------------------------

--
-- Table structure for table `order_list`
--

CREATE TABLE `order_list` (
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `length_feet` int(11) DEFAULT NULL,
  `width_feet` int(11) DEFAULT NULL,
  `height_feet` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_path` varchar(255) DEFAULT NULL,
  `status_updated_by` int(11) DEFAULT NULL,
  `status_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_list`
--

INSERT INTO `order_list` (`order_id`, `buyer_id`, `product_name`, `length_feet`, `width_feet`, `height_feet`, `quantity`, `status`, `created_at`, `receipt_path`, `status_updated_by`, `status_updated_at`) VALUES
(36, 2, 'Coconut', 2, 3, 8, 8, 'Product Received', '2025-04-24 01:53:08', NULL, 3, '2025-04-24 01:55:33'),
(37, 2, 'Coconut', 2, 3, 8, 10, 'Product Received', '2025-04-24 02:04:58', NULL, 3, '2025-04-24 02:06:52'),
(38, 2, 'Coconut', 2, 3, 8, 7, 'Product Received', '2025-04-24 02:08:55', NULL, 3, '2025-04-24 02:10:06');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `gcash_number` varchar(20) DEFAULT NULL,
  `gcash_name` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `payment_date` datetime NOT NULL,
  `product_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `receipt_id`, `payment_method`, `reference_number`, `gcash_number`, `gcash_name`, `screenshot_path`, `delivery_address`, `contact_number`, `delivery_notes`, `pickup_date`, `pickup_time`, `payment_status`, `payment_date`, `product_notes`) VALUES
(30, 20, 'gcash', 'GC17454596469169', '09123456789', 'aw', 'uploads/payments/1745459646_receipt_20.png', NULL, '09123456789', NULL, NULL, NULL, 'pending', '2025-04-24 09:54:06', ''),
(31, 21, 'gcash', 'GC17454603891221', '09123456789', 'Lumber', 'uploads/payments/1745460389_Untitled Diagram.drawio.png', NULL, '09123456789', NULL, NULL, NULL, 'pending', '2025-04-24 10:06:29', 'aw'),
(32, 22, 'pickup', 'PI17454605878277', NULL, NULL, NULL, NULL, '09552652214', NULL, '2025-04-24', '17:00:00', 'pending', '2025-04-24 10:09:47', '');

-- --------------------------------------------------------

--
-- Table structure for table `product_catalog`
--

CREATE TABLE `product_catalog` (
  `catalog_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `price_per_sqft` decimal(10,2) DEFAULT NULL,
  `length_feet` int(11) DEFAULT NULL,
  `width_feet` int(11) DEFAULT NULL,
  `height_feet` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_catalog`
--

INSERT INTO `product_catalog` (`catalog_id`, `product_id`, `seller_id`, `product_name`, `price_per_sqft`, `length_feet`, `width_feet`, `height_feet`, `quantity`, `image_path`, `created_at`, `updated_at`) VALUES
(13, 0, 1, 'Coconut', 25.00, 2, 3, 8, 25, './uploads/inventory/product_12_1741669288.jpg', '2025-03-11 06:31:07', '2025-03-11 06:31:13'),
(14, 0, 1, 'Mahogany', 51.00, 8, 8, 8, 25, './uploads/inventory/product_14_1741669371.jpg', '2025-03-11 06:31:23', '2025-03-18 04:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory`
--

CREATE TABLE `product_inventory` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `length_feet` int(11) NOT NULL,
  `width_feet` int(11) NOT NULL,
  `height_feet` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_square_feet` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_inventory`
--

INSERT INTO `product_inventory` (`product_id`, `seller_id`, `product_name`, `length_feet`, `width_feet`, `height_feet`, `quantity`, `total_square_feet`, `image_path`, `created_at`, `updated_at`) VALUES
(12, 1, 'Coconut', 2, 3, 8, 0, 48, './uploads/inventory/product_12_1741669288.jpg', '2025-03-11 05:01:28', '2025-04-24 02:09:47'),
(13, 1, 'Coconut', 3, 8, 9, 25, 216, NULL, '2025-03-11 05:02:32', '2025-03-11 05:02:32'),
(14, 1, 'Mahogany', 8, 8, 8, 25, 512, './uploads/inventory/product_14_1741669371.jpg', '2025-03-11 05:02:51', '2025-03-11 05:02:51'),
(16, 1, 'Mangga', 5, 5, 8, 120, 0, 'uploads/products/product_67fe147fa65be.png', '2025-04-15 08:10:39', '2025-04-15 08:10:39'),
(18, 1, 'Mangga', 3, 3, 6, 20, 0, NULL, '2025-04-15 08:28:08', '2025-04-15 08:28:08'),
(19, 1, 'Mangga', 2, 2, 5, 10, 0, NULL, '2025-04-15 08:28:29', '2025-04-15 08:28:29');

-- --------------------------------------------------------

--
-- Table structure for table `product_trash`
--

CREATE TABLE `product_trash` (
  `trash_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `length_feet` int(11) DEFAULT NULL,
  `width_feet` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price_per_sqft` decimal(10,2) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(20) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `buyer_name` varchar(255) DEFAULT NULL,
  `seller_id` int(11) NOT NULL,
  `receipt_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`id`, `receipt_number`, `order_id`, `product_name`, `size`, `quantity`, `total_amount`, `buyer_name`, `seller_id`, `receipt_date`) VALUES
(20, 'RCP-20250424-9298', 36, 'Coconut', '2\' x 3\' x 8\'', 8, 200.00, 'Buyer Lumber', 1, '2025-04-24 03:53:37'),
(21, 'RCP-20250424-9123', 37, 'Coconut', '2\' x 3\' x 8\'', 10, 250.00, 'Buyer Lumber', 1, '2025-04-24 04:05:34'),
(22, 'RCP-20250424-7668', 38, 'Coconut', '2\' x 3\' x 8\'', 7, 175.00, 'Buyer Lumber', 1, '2025-04-24 04:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `seller_ids`
--

CREATE TABLE `seller_ids` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `seller_unique_id` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_ids`
--

INSERT INTO `seller_ids` (`id`, `user_id`, `seller_unique_id`, `created_at`) VALUES
(1, 1, 'S28500', '2025-04-02 02:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `sup_orders`
--

CREATE TABLE `sup_orders` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `scheduled_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sup_orders`
--

INSERT INTO `sup_orders` (`id`, `seller_id`, `supplier_id`, `item_id`, `status`, `created_at`, `is_read`, `scheduled_date`) VALUES
(23, 1, 5, 6, 'Scheduled', '2025-04-21 07:00:41', 0, NULL),
(24, 1, 5, 5, 'Pending', '2025-04-21 07:00:47', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID` int(25) NOT NULL,
  `lastname` varchar(25) NOT NULL,
  `firstname` varchar(25) NOT NULL,
  `email` varchar(25) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `contactno` text NOT NULL,
  `address` text NOT NULL,
  `role` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL,
  `employer_id` int(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID`, `lastname`, `firstname`, `email`, `username`, `password`, `contactno`, `address`, `role`, `created_at`, `profile_photo`, `employer_id`) VALUES
(1, 'Deala', 'Carl Jevan', 'carljevan.dulasan@gmail.c', 'carl', '$2y$10$pqRrbpeYXHZUIUkWLymcwOkO307GK7gSzvTpTKmTwemu4f8hmWs2u', '09552652217', 'Batolusa, Bankas HTS., Toril, Davao City', 'Seller', '2025-03-10 05:50:20', 'uploads/profile_photos/67cea259beb55_1741595225.jpg', NULL),
(2, 'Lumber', 'Buyer', 'buyer@gmail.com', 'buyer', '$2y$10$EqEP58qAh7dyI10AJKd2Luia/etVgtfQ1ILHKGGpeWVhSpBkUtpS6', '09552652214', 'Lobugan, Toril, Davao City', 'Buyer', '2025-03-11 00:54:31', 'uploads/profile_photos/67cf8f5708c60_1741655895.jpg', NULL),
(3, 'Labor', 'Laborer', 'laborer@gmail.com', 'labor', '$2y$10$yjMVXvqv/j5Z/HgQU0ZRbet3YeJLt2eFWx5mDrmJLt6seksOOW7fC', '09321456987', 'Lobugan, Toril, Davao City', 'Laborer', '2025-04-02 02:19:10', 'uploads/profile_photos/67ecc04605fe2_1743568966.jpg', 1),
(5, 'Tolentino', 'Nazareth', 'naz@gmail.com', 'naz', '$2y$10$eDAoJqxS5gpCqbu0jXAznuHk3gePkQPkIrdeclWabRyqyUH82Ad36', '09174852396', 'Crasher Manggahan, Toril, Davao City', 'Supplier', '2025-04-15 03:11:06', 'uploads/profile_photos/67fdd21dc642c_1744687645.jpg', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cus_orders`
--
ALTER TABLE `cus_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `delivery_schedules`
--
ALTER TABLE `delivery_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `presentation_id` (`presentation_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `for_pickup`
--
ALTER TABLE `for_pickup`
  ADD PRIMARY KEY (`pickup_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `gcash`
--
ALTER TABLE `gcash`
  ADD PRIMARY KEY (`gcash_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_list`
--
ALTER TABLE `order_list`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`);

--
-- Indexes for table `product_catalog`
--
ALTER TABLE `product_catalog`
  ADD PRIMARY KEY (`catalog_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `product_trash`
--
ALTER TABLE `product_trash`
  ADD PRIMARY KEY (`trash_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fk_receipt_seller` (`seller_id`);

--
-- Indexes for table `seller_ids`
--
ALTER TABLE `seller_ids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `seller_unique_id` (`seller_unique_id`);

--
-- Indexes for table `sup_orders`
--
ALTER TABLE `sup_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `employer_id` (`employer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cus_orders`
--
ALTER TABLE `cus_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `daily_sales`
--
ALTER TABLE `daily_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `delivery_schedules`
--
ALTER TABLE `delivery_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `for_pickup`
--
ALTER TABLE `for_pickup`
  MODIFY `pickup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `gcash`
--
ALTER TABLE `gcash`
  MODIFY `gcash_id` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_list`
--
ALTER TABLE `order_list`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `product_catalog`
--
ALTER TABLE `product_catalog`
  MODIFY `catalog_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_inventory`
--
ALTER TABLE `product_inventory`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `product_trash`
--
ALTER TABLE `product_trash`
  MODIFY `trash_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipt`
--
ALTER TABLE `receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `seller_ids`
--
ALTER TABLE `seller_ids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sup_orders`
--
ALTER TABLE `sup_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD CONSTRAINT `daily_sales_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `delivery_schedules`
--
ALTER TABLE `delivery_schedules`
  ADD CONSTRAINT `delivery_schedules_ibfk_1` FOREIGN KEY (`presentation_id`) REFERENCES `sup_orders` (`id`),
  ADD CONSTRAINT `delivery_schedules_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `delivery_schedules_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `for_pickup`
--
ALTER TABLE `for_pickup`
  ADD CONSTRAINT `for_pickup_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_list` (`order_id`);

--
-- Constraints for table `gcash`
--
ALTER TABLE `gcash`
  ADD CONSTRAINT `gcash_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `sup_orders` (`item_id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `order_list`
--
ALTER TABLE `order_list`
  ADD CONSTRAINT `order_list_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `receipt` (`id`);

--
-- Constraints for table `product_catalog`
--
ALTER TABLE `product_catalog`
  ADD CONSTRAINT `product_catalog_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD CONSTRAINT `product_inventory_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `product_trash`
--
ALTER TABLE `product_trash`
  ADD CONSTRAINT `product_trash_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `receipt`
--
ALTER TABLE `receipt`
  ADD CONSTRAINT `fk_receipt_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `receipt_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_list` (`order_id`);

--
-- Constraints for table `seller_ids`
--
ALTER TABLE `seller_ids`
  ADD CONSTRAINT `seller_ids_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
