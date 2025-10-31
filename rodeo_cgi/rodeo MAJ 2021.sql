-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Client :  localhost:3306
-- Généré le :  Jeu 26 Décembre 2019 à 09:54
-- Version du serveur :  10.1.41-MariaDB-0+deb9u1
-- Version de PHP :  7.0.33-0+deb9u6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `rodeo`
--

-- --------------------------------------------------------

--
-- Structure de la table `fees_2021`
--

CREATE TABLE `fees_2021` (
  `userid` int(11) NOT NULL DEFAULT '0',
  `projid` int(11) NOT NULL DEFAULT '0',
  `month1` int(11) NOT NULL DEFAULT '0',
  `month2` int(11) NOT NULL DEFAULT '0',
  `fee` float NOT NULL DEFAULT '0',
  `type` int(11) DEFAULT NULL,
  `percentage` int(11) NOT NULL DEFAULT '0',
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `invoices_2021`
--

CREATE TABLE `invoices_2021` (
  `projid` int(11) NOT NULL DEFAULT '0',
  `week1` int(11) NOT NULL DEFAULT '0',
  `week2` int(11) NOT NULL DEFAULT '0',
  `month` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `hours` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `date_created` char(10) NOT NULL DEFAULT '',
  `posted_by` int(11) DEFAULT NULL,
  `date_posted` char(10) DEFAULT NULL,
  `payment_received` char(10) DEFAULT NULL,
  `payment_due` char(10) DEFAULT NULL,
  `date_paid` int(11) DEFAULT NULL,
  `datum` char(10) DEFAULT NULL,
  `ID` char(10) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `reports_2021`
--

CREATE TABLE `reports_2021` (
  `userid` int(11) NOT NULL DEFAULT '0',
  `week` int(11) NOT NULL DEFAULT '0',
  `projid` int(11) NOT NULL DEFAULT '0',
  `actid` int(11) NOT NULL DEFAULT '0',
  `timecode` int(11) DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  `monday` decimal(3,1) DEFAULT NULL,
  `tuesday` decimal(3,1) DEFAULT NULL,
  `wednesday` decimal(3,1) DEFAULT NULL,
  `thursday` decimal(3,1) DEFAULT NULL,
  `friday` decimal(3,1) DEFAULT NULL,
  `saturday` decimal(3,1) DEFAULT NULL,
  `sunday` decimal(3,1) DEFAULT NULL,
  `theme` varchar(15) DEFAULT NULL,
  `commentaire` varchar(60) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `workhours_2021`
--

CREATE TABLE `workhours_2021` (
  `week` int(11) NOT NULL DEFAULT '0',
  `freedays` varchar(10) DEFAULT NULL,
  `hours` int(11) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Contenu de la table `workhours_2021`
--

INSERT INTO `workhours_2021` (`week`, `freedays`, `hours`, `comment`) VALUES
(1, '1', 32, 'Nouvelle année'),
(2, NULL, 40, NULL),
(3, NULL, 40, NULL),
(4, NULL, 40, NULL),
(5, NULL, 40, NULL),
(6, NULL, 40, NULL),
(7, NULL, 40, NULL),
(8, NULL, 40, NULL),
(9, NULL, 40, NULL),
(10, NULL, 40, NULL),
(11, NULL, 40, NULL),
(12, NULL, 40, NULL),
(13, NULL, 40, NULL),
(14, '1', 32, 'Lundi de Pâques'),
(15, NULL, 40, NULL),
(16, NULL, 40, NULL),
(17, NULL, 40, NULL),
(18, '2', 32, 'Fête du travail'),
(19, '2,4', 32, 'Armistice 39/45\r\nJeudi'),
(20, NULL, 40, NULL),
(21, '1', 32, 'Ascension'),
(22, NULL, 40, NULL),
(23, '1', 32, 'Pentecote'),
(24, NULL, 40, NULL),
(25, NULL, 40, NULL),
(26, NULL, 40, NULL),
(27, NULL, 40, NULL),
(28, '2', 32, 'Fête nationale'),
(29, NULL, 40, NULL),
(31, NULL, 40, NULL),
(32, NULL, 40, NULL),
(33, '3', 32, 'Assomption'),
(34, NULL, 40, NULL),
(35, NULL, 40, NULL),
(36, NULL, 40, NULL),
(37, NULL, 40, NULL),
(38, NULL, 40, NULL),
(39, NULL, 40, NULL),
(40, NULL, 40, NULL),
(41, NULL, 40, NULL),
(42, NULL, 40, NULL),
(43, NULL, 40, NULL),
(44, '4', 32, 'TOUSSAINT'),
(45, '1', 40, 'Armistice 1914/1918'),
(46, NULL, 40, NULL),
(47, NULL, 40, NULL),
(48, NULL, 40, NULL),
(49, NULL, 40, NULL),
(50, NULL, 40, NULL),
(51, NULL, 40, NULL),
(52, '2', 32, 'Noel'),
(53, NULL, 32, NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
