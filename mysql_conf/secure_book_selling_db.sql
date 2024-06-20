-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql-server
-- Generation Time: Jun 18, 2024 at 05:26 PM
-- Server version: 8.4.0
-- PHP Version: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `secure_book_selling_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book`
(
    `id`            smallint    NOT NULL,
    `title`         varchar(64) NOT NULL,
    `author`        varchar(64) DEFAULT NULL,
    `publisher`     varchar(64) DEFAULT NULL,
    `price`         float       NOT NULL,
    `category`      varchar(64) DEFAULT NULL,
    `stocks_number` int         NOT NULL,
    `ebook_name`    varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`id`, `title`, `author`, `publisher`, `price`, `category`, `stocks_number`, `ebook_name`)
VALUES (1, 'The Odissey', 'Homer', 'W W Norton & Co Inc', 16.15, 'Epic Poetry', 10, '1.pdf'),
       (2, 'Orlando Furioso', 'Ludovico Ariosto', 'Rizzoli', 50.49, 'Chivalric Epic Poem', 24, '2.pdf'),
       (3, 'Naruto Vol.2', 'Masashi Kishimoto', 'Panini Comics', 5.29, 'Manga', 37, '3.pdf'),
       (4, 'Uno, nessuno e centomila', 'Luigi Pirandello', 'Crescere', 4.28, 'Novel', 15, '4.pdf'),
       (5, 'David Copperfield', 'Charles Dickens', 'Einaudi', 16.62, 'Novel', 26, '5.pdf'),
       (6, 'One Piece Vol.97', 'Eiichiro Oda', 'Star Comics', 4.94, 'Manga', 10, '6.pdf'),
       (7, 'The Adventures of Sherlock Holmes and Other Stories', 'Arthur Conan Doyle', 'Canterbury Classics', 19.53,
        'Collection of Short Stories', 15, '7.pdf'),
       (8, 'Attack on Titan Vol.34', 'Hajime Isayama', 'Panini Comics', 5.19, 'Manga', 15, '8.pdf'),
       (9, 'La coscienza di Zeno', 'Italo Svevo', 'Mondadori', 13.77, 'Novel', 5, '9.pdf'),
       (10, 'Three Musketeers ', 'Alexandre Dumas', 'Wordsworth Editions Ltd', 4.23, ' Historical Romance', 17,
        '10.pdf'),
       (11, 'The Picture of Dorian Gray', 'Oscar Wilde', 'Penguin Classics', 11.39, 'Novel', 12, '11.pdf'),
       (12, 'The Hobbit', 'J.R.R. Tolkien', 'HarperCollinsChildren’sBooks', 10.5, 'Fantasy', 8, '12.pdf'),
       (13, 'The Great Gatsby', 'Francis Scott Fitzgerald', 'Liberty', 10.75, 'Novel', 23, '13.pdf'),
       (14, 'Dragon Ball Ultimate Edition Vol.22', ' Akira Toriyama ', 'Star Comics', 14.25, 'Manga', 28, '14.pdf'),
       (15, 'The World of Ice and Fire', 'George R.R. Martin', 'HarperVoyager', 42.37, 'Fantasy', 18, '15.pdf'),
       (16, 'Don Quijote', 'Miguel de Cervantes Saavedra', 'Anaconda', 12.58, 'Novel', 29, '16.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase`
(
    `id_user`        smallint    NOT NULL,
    `id_book`        smallint    NOT NULL,
    `time`           timestamp   NOT NULL,
    `amount`         float       NOT NULL,
    `quantity`       int         NOT NULL,
    `payment_method` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user`
(
    `id`              smallint    NOT NULL,
    `password`        varchar(255) NOT NULL,
    `salt`            varchar(64) NOT NULL,
    `email`           varchar(64) NOT NULL,
    `first_name`      varchar(64) NOT NULL,
    `last_name`       varchar(64) NOT NULL,
    `address`         varchar(64) NOT NULL,
    `city`            varchar(64) NOT NULL,
    `province`        varchar(2)  NOT NULL,
    `postal_code`     int(5) UNSIGNED ZEROFILL NOT NULL,
    `country`         varchar(64) NOT NULL,
    `timestampAccess` timestamp NULL DEFAULT NULL,
    `failedAccesses`  smallint    NOT NULL DEFAULT '0',
    `blockedTime`     int         NOT NULL DEFAULT '0',
    `otp`             varchar(64)          DEFAULT NULL,
    `lastOtp`         timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `book`
--
ALTER TABLE `book`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
    ADD PRIMARY KEY (`id_user`, `id_book`, `time`),
  ADD KEY `purchase_book_id_fk` (`id_book`),
  ADD KEY `purchase_user_id_fk` (`id_user`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_unique_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
    MODIFY `id` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
    MODIFY `id` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
    ADD CONSTRAINT `purchase_book_id_fk` FOREIGN KEY (`id_book`) REFERENCES `book` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `purchase_user_id_fk` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON
DELETE
CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;