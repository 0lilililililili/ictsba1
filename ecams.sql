-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2026 年 08 月 27 日 05:11
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `ecams`
--

-- --------------------------------------------------------

--
-- 資料表結構 `activity`
--

CREATE TABLE `activity` (
  `aid` char(5) NOT NULL,
  `aname` varchar(40) DEFAULT NULL,
  `adate` datetime DEFAULT NULL,
  `venue` varchar(40) DEFAULT NULL,
  `attendance` decimal(4,4) DEFAULT NULL,
  `cid` char(5) DEFAULT NULL,
  `stuMon` char(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `activity`
--

INSERT INTO `activity` (`aid`, `aname`, `adate`, `venue`, `attendance`, `cid`, `stuMon`) VALUES
('a0001', 'computer components', '2023-07-13 00:00:00', 'Rm342', 0.8000, 'c0001', 's0002'),
('a0002', 'QHED', '2026-08-18 00:00:00', 'Rm624', 0.8000, 'c0002', 's0001'),
('a0003', 'radioactivity', '2026-08-03 00:00:00', '6/F physics lab', 0.9999, 'c0002', 's0001'),
('a0004', 'interschool swimming competition', '2025-05-29 00:00:00', 'school swimming pool', 0.8000, 'c0004', 's0007'),
('a0005', 'shors algorithm', '2026-07-21 00:00:00', 'Rm624', 0.6667, 'c0002', 's0001'),
('a0006', 'Christmas fair', '2026-12-19 00:00:00', 'multipurpose hall', NULL, 'c0002', 's0003'),
('a0007', 'astronomy talk', '2026-03-17 00:00:00', 'small hall', NULL, 'c0002', 's0001');

-- --------------------------------------------------------

--
-- 資料表結構 `club`
--

CREATE TABLE `club` (
  `cid` char(5) NOT NULL,
  `cname` varchar(40) DEFAULT NULL,
  `tid` char(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `club`
--

INSERT INTO `club` (`cid`, `cname`, `tid`) VALUES
('c0001', 'ict club', 't0001'),
('c0002', 'physics club', 't0002'),
('c0003', 'math club', 't0003'),
('c0004', 'PE team', 't0004');

-- --------------------------------------------------------

--
-- 資料表結構 `enrollment`
--

CREATE TABLE `enrollment` (
  `sid` char(5) NOT NULL,
  `cid` char(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `enrollment`
--

INSERT INTO `enrollment` (`sid`, `cid`) VALUES
('s0001', 'c0001'),
('s0001', 'c0002'),
('s0002', 'c0002'),
('s0003', 'c0002'),
('s0004', 'c0002'),
('s0004', 'c0003'),
('s0004', 'c0004'),
('s0005', 'c0002'),
('s0005', 'c0003'),
('s0005', 'c0004'),
('s0006', 'c0001'),
('s0006', 'c0004'),
('s0007', 'c0001'),
('s0007', 'c0004'),
('s0008', 'c0001'),
('s0008', 'c0002');

-- --------------------------------------------------------

--
-- 資料表結構 `participation`
--

CREATE TABLE `participation` (
  `sid` char(5) DEFAULT NULL,
  `aid` char(5) DEFAULT NULL,
  `status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `participation`
--

INSERT INTO `participation` (`sid`, `aid`, `status`) VALUES
('s0001', 'a0002', 1),
('s0002', 'a0002', 2),
('s0004', 'a0002', 3),
('s0005', 'a0001', 1),
('s0006', 'a0001', 1),
('s0007', 'a0001', 3),
('s0008', 'a0001', 2),
('s0001', 'a0003', 2),
('s0002', 'a0003', 2),
('s0003', 'a0003', 1),
('s0004', 'a0003', 1),
('s0005', 'a0004', 1),
('s0006', 'a0004', 1),
('s0007', 'a0004', 1),
('s0008', 'a0004', 1),
('s0001', 'a0005', 3),
('s0002', 'a0005', 2),
('s0007', 'a0002', 1),
('s0002', 'a0001', 2),
('s0003', 'a0002', 2),
('s0008', 'a0003', 1),
('s0001', 'a0004', 3),
('s0003', 'a0005', 2);

--
-- 觸發器 `participation`
--
DELIMITER $$
CREATE TRIGGER `datadel` AFTER DELETE ON `participation` FOR EACH ROW begin
    update activity 
    set attendance = (
        select ifnull(sum(case when status in (1, 2) then 1 else 0 end), 0) / count(*)
        from participation where aid = old.aid group by aid )
    where aid = old.aid;
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `datain` AFTER INSERT ON `participation` FOR EACH ROW begin
    update activity 
    set attendance = (
        select ifnull(sum(case when status in (1, 2) then 1 else 0 end), 0) / count(*) 
        from participation where aid = new.aid group by aid)
    where aid = new.aid;
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `dataup` AFTER UPDATE ON `participation` FOR EACH ROW begin
    update activity 
    set attendance = (
        select ifnull(sum(case when status in (1, 2) then 1 else 0 end), 0) / count(*)
        from participation where aid = new.aid group by aid )
    where aid = new.aid;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 資料表結構 `student`
--

CREATE TABLE `student` (
  `sid` char(5) NOT NULL,
  `sname` varchar(40) DEFAULT NULL,
  `scls` char(2) DEFAULT NULL,
  `scno` int(11) DEFAULT NULL,
  `role` varchar(40) DEFAULT NULL,
  `pw` char(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `student`
--

INSERT INTO `student` (`sid`, `sname`, `scls`, `scno`, `role`, `pw`) VALUES
('s0001', 'Linderr BB', '6A', 3, 'monitor', '12345678'),
('s0002', 'Chan Tai Man', '4B', 15, 'monitor', '12345678'),
('s0003', 'Brocco Li', '2E', 33, 'monitor', '12345678'),
('s0004', 'Sum Ting Wong', '5D', 20, 'student', '12345678'),
('s0005', 'Mary Lau', '2A', 11, 'student', '12345678'),
('s0006', 'Chris Wong', '6B', 24, 'student', '12345678'),
('s0007', 'Jacky Chu', '6A', 1, 'monitor', '12345678'),
('s0008', 'Kelvin Yeung', '6A', 2, 'student', '12345678');

-- --------------------------------------------------------

--
-- 資料表結構 `teacher`
--

CREATE TABLE `teacher` (
  `tid` char(5) NOT NULL,
  `tname` varchar(40) DEFAULT NULL,
  `role` varchar(40) DEFAULT NULL,
  `pw` char(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `teacher`
--

INSERT INTO `teacher` (`tid`, `tname`, `role`, `pw`) VALUES
('t0001', 'Linda', 'admin', '12345678'),
('t0002', 'Ryan', 'teacher', '12345678'),
('t0003', 'Dr', 'teacher', '12345678'),
('t0004', 'Rose', 'teacher', '12345678');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `activity`
--
ALTER TABLE `activity`
  ADD PRIMARY KEY (`aid`),
  ADD KEY `fk4` (`cid`),
  ADD KEY `fk5` (`stuMon`);

--
-- 資料表索引 `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`cid`),
  ADD KEY `fk1` (`tid`);

--
-- 資料表索引 `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`sid`,`cid`),
  ADD KEY `fk3` (`cid`);

--
-- 資料表索引 `participation`
--
ALTER TABLE `participation`
  ADD KEY `fk6` (`sid`),
  ADD KEY `fk7` (`aid`);

--
-- 資料表索引 `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`sid`);

--
-- 資料表索引 `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`tid`);

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `activity`
--
ALTER TABLE `activity`
  ADD CONSTRAINT `fk4` FOREIGN KEY (`cid`) REFERENCES `club` (`cid`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk5` FOREIGN KEY (`stuMon`) REFERENCES `student` (`sid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 資料表的限制式 `club`
--
ALTER TABLE `club`
  ADD CONSTRAINT `fk1` FOREIGN KEY (`tid`) REFERENCES `teacher` (`tid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 資料表的限制式 `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `fk2` FOREIGN KEY (`sid`) REFERENCES `student` (`sid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk3` FOREIGN KEY (`cid`) REFERENCES `club` (`cid`) ON DELETE CASCADE;

--
-- 資料表的限制式 `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `fk6` FOREIGN KEY (`sid`) REFERENCES `student` (`sid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk7` FOREIGN KEY (`aid`) REFERENCES `activity` (`aid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
