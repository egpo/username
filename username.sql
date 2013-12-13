-- Create the username exmaple database tables.
-- The ewxample can be downloaded from http://webdev.egpo.net

-- --------------------------------------------------------

--
-- Table structure for table `username`
--

CREATE TABLE IF NOT EXISTS `username` (
    `uname` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `username_temp`
--

CREATE TABLE IF NOT EXISTS `username_temp` (
    `uname` varchar(20) NOT NULL,
    `sess_id` varchar(64) NOT NULL,
    `timestamp` int(11) NOT NULL,
    UNIQUE KEY `uname` (`uname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
