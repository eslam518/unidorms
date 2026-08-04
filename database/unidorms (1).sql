
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2026 at 12:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

DROP DATABASE IF EXISTS unidorms;
CREATE DATABASE unidorms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unidorms;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: unidorms
--

-- --------------------------------------------------------

--
-- Table structure for table buildings
--

CREATE TABLE buildings (
  id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  address varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table buildings
--

INSERT INTO buildings (id, name, address) VALUES
(1, 'West Dorm', '12 Campus Lane'),
(2, 'South Dorm', '5 University Row');

-- --------------------------------------------------------

--
-- Table structure for table contact_messages
--

CREATE TABLE contact_messages (
  id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(150) NOT NULL,
  message text NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table maintenance_requests
--

CREATE TABLE maintenance_requests (
  id int(11) NOT NULL,
  room_id int(11) NOT NULL,
  student_id int(11) DEFAULT NULL,
  title varchar(150) NOT NULL,
  description text DEFAULT NULL,
  priority enum('low','medium','high') NOT NULL DEFAULT 'medium',
  status enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  resolved_at timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table password_resets
--

CREATE TABLE password_resets (
  id int(11) NOT NULL,
  email varchar(150) NOT NULL,
  token varchar(100) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table payments
--

CREATE TABLE payments (
  id int(11) NOT NULL,
  student_id int(11) NOT NULL,
  amount decimal(10,2) NOT NULL,
  payment_type varchar(60) NOT NULL DEFAULT 'Semester Fee',
  due_date date NOT NULL,
  paid_date date DEFAULT NULL,
  status enum('paid','pending','overdue') NOT NULL DEFAULT 'pending',
  notes varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table payments
--

INSERT INTO payments (id, student_id, amount, payment_type, due_date, paid_date, status, notes) VALUES
(1, 1, 900.00, 'Booking Fee', '2026-08-03', '2026-08-03', 'paid', 'Paid via student checkout for request 1'),
(2, 1, 900.00, 'Booking Fee', '2026-08-03', '2026-08-03', 'paid', 'Paid via student checkout for request 1');

-- --------------------------------------------------------

--
-- Table structure for table rooms
--

CREATE TABLE rooms (
  id int(11) NOT NULL,
  building_id int(11) NOT NULL,
  room_number varchar(20) NOT NULL,
  floor int(11) NOT NULL,
  capacity int(11) NOT NULL DEFAULT 2,
  occupied int(11) NOT NULL DEFAULT 0,
  price_per_term decimal(10,2) DEFAULT 0.00,
  room_type enum('standard','deluxe','single') NOT NULL DEFAULT 'standard',
  status enum('available','full','maintenance') NOT NULL DEFAULT 'available',
  room_image varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table rooms
--

INSERT INTO rooms (id, building_id, room_number, floor, capacity, occupied, price_per_term, room_type, status, room_image) VALUES
(1, 1, '101', 1, 2, 0, 900.00, 'standard', 'available', 'room13.jpeg'),
(2, 1, '102', 1, 2, 0, 900.00, 'standard', 'available', 'room25.jpeg'),
(3, 1, '103', 1, 1, 0, 1400.00, 'single', 'available', 'room22.jpeg'),
(4, 1, '104', 1, 2, 0, 900.00, 'standard', 'maintenance', 'room20.jpeg'),
(5, 1, '201', 2, 2, 0, 900.00, 'standard', 'available', 'room30.jpeg'),
(6, 1, '202', 2, 2, 0, 900.00, 'standard', 'available', 'room18.jpeg'),
(7, 1, '203', 2, 2, 0, 1200.00, 'deluxe', 'available', 'room20.jpeg'),
(8, 1, '204', 2, 2, 0, 900.00, 'standard', 'available', 'room22.jpeg'),
(9, 1, '301', 3, 2, 0, 900.00, 'standard', 'available', 'room13.jpeg'),
(10, 1, '302', 3, 1, 0, 1400.00, 'single', 'available', 'room30.jpeg'),
(11, 2, '101', 1, 2, 0, 900.00, 'standard', 'available', 'room25.jpeg'),
(12, 2, '102', 1, 2, 0, 900.00, 'standard', 'available', 'room20.jpeg'),
(13, 2, '103', 1, 2, 0, 1200.00, 'deluxe', 'available', 'room18.jpeg'),
(14, 2, '201', 2, 2, 0, 900.00, 'standard', 'maintenance', 'room22.jpeg'),
(15, 2, '202', 2, 2, 0, 900.00, 'standard', 'available', 'room13.jpeg'),
(16, 2, '203', 2, 1, 0, 1400.00, 'single', 'available', 'room30.jpeg'),
(17, 2, '301', 3, 2, 0, 900.00, 'standard', 'available', 'room25.jpeg'),
(18, 2, '302', 3, 2, 1, 900.00, 'standard', 'available', 'room18.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table room_requests
--

CREATE TABLE room_requests (
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  room_id int(11) NOT NULL,
  status enum('pending','accepted','rejected') DEFAULT 'pending',
  paid_at timestamp NULL DEFAULT NULL,
  requested_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table room_requests
--

INSERT INTO room_requests (id, user_id, room_id, status, paid_at, requested_at) VALUES
(1, 2, 18, 'accepted', '2026-08-03 10:02:43', '2026-08-03 08:23:56');

-- --------------------------------------------------------

--
-- Table structure for table students
--

CREATE TABLE students (
  id int(11) NOT NULL,
  user_id int(11) DEFAULT NULL,
  first_name varchar(60) NOT NULL,
  last_name varchar(60) NOT NULL,
  student_id_number varchar(30) NOT NULL,
  email varchar(120) DEFAULT NULL,
  phone varchar(30) DEFAULT NULL,
  gender enum('male','female','other') DEFAULT 'other',
  room_id int(11) DEFAULT NULL,
  check_in_date date DEFAULT NULL,
  status enum('active','checked_out') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table students
--

INSERT INTO students (id, user_id, first_name, last_name, student_id_number, email, phone, gender, room_id, check_in_date, status) VALUES
(1, 2, 'kiro', '', 'STU0002', 'kiro@university.edu.eg', NULL, 'other', 18, '2026-08-03', 'active');

-- --------------------------------------------------------

--
-- Table structure for table users
--

CREATE TABLE users (
  id int(11) NOT NULL,
  full_name varchar(100) NOT NULL,
  email varchar(150) NOT NULL,
  password varchar(255) NOT NULL,
  role enum('admin','student') NOT NULL DEFAULT 'student',
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table users
--

INSERT INTO users (id, full_name, email, password, role, created_at) VALUES
(1, 'Dorm Administrator', 'admin@dorm.com', '$2y$10$vHa8ixeTpPF37Do1SB5DoenoDRr.uv8PG.W7RwpI5.S5vf58gxI8a', 'admin', '2026-08-03 06:43:29'),
(2, 'kiro', 'kiro@university.edu.eg', '$2y$10$rNqNwvI15HRbvnOeJQQ22eLMzMyNrFBPzQ.3s7ESCeDOrpf9/5Udm', 'student', '2026-08-03 06:53:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table buildings
--
ALTER TABLE buildings
  ADD PRIMARY KEY (id);

--
-- Indexes for table contact_messages
--
ALTER TABLE contact_messages
  ADD PRIMARY KEY (id);

--
-- Indexes for table maintenance_requests
--
ALTER TABLE maintenance_requests
  ADD PRIMARY KEY (id),
  ADD KEY room_id (room_id),
  ADD KEY student_id (student_id);

--
-- Indexes for table password_resets
--
ALTER TABLE password_resets
  ADD PRIMARY KEY (id);

--
-- Indexes for table payments
--
ALTER TABLE payments
  ADD PRIMARY KEY (id),
  ADD KEY student_id (student_id);

--
-- Indexes for table rooms
--
ALTER TABLE rooms
  ADD PRIMARY KEY (id),
  ADD KEY building_id (building_id);

--
-- Indexes for table room_requests
--
ALTER TABLE room_requests
  ADD PRIMARY KEY (id),
  ADD KEY user_id (user_id),
  ADD KEY room_id (room_id);

--
-- Indexes for table students
--
ALTER TABLE students
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY student_id_number (student_id_number),
  ADD UNIQUE KEY user_id (user_id),
  ADD KEY room_id (room_id);

--
-- Indexes for table users
--
ALTER TABLE users
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY email (email);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table buildings
--
ALTER TABLE buildings
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table contact_messages
--
ALTER TABLE contact_messages
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table maintenance_requests
--
ALTER TABLE maintenance_requests
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table password_resets
--
ALTER TABLE password_resets
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table payments
--
ALTER TABLE payments
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table rooms
--
ALTER TABLE rooms
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table room_requests
--
ALTER TABLE room_requests
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table students
--
ALTER TABLE students
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table users
--
ALTER TABLE users
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table maintenance_requests
--
ALTER TABLE maintenance_requests
  ADD CONSTRAINT maintenance_requests_ibfk_1 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE,
  ADD CONSTRAINT maintenance_requests_ibfk_2 FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE SET NULL;

--
-- Constraints for table payments
--
ALTER TABLE payments
  ADD CONSTRAINT payments_ibfk_1 FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE;

--
-- Constraints for table rooms
--
ALTER TABLE rooms
  ADD CONSTRAINT rooms_ibfk_1 FOREIGN KEY (building_id) REFERENCES buildings (id) ON DELETE CASCADE;

--
-- Constraints for table room_requests
--
ALTER TABLE room_requests
  ADD CONSTRAINT room_requests_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  ADD CONSTRAINT room_requests_ibfk_2 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE;

--
-- Constraints for table students
--
ALTER TABLE students
  ADD CONSTRAINT students_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  ADD CONSTRAINT students_ibfk_2 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;