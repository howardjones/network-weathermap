-- MySQL dump 10.16  Distrib 10.1.13-MariaDB, for osx10.6 (i386)
--
-- Host: localhost    Database: cacti
-- ------------------------------------------------------
-- Server version	10.1.13-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `weathermap_maps`
--

DROP TABLE IF EXISTS `weathermap_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weathermap_maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sortorder` int(11) NOT NULL DEFAULT '0',
  `group_id` int(11) NOT NULL DEFAULT '1',
  `active` set('on','off') NOT NULL DEFAULT 'on',
  `configfile` text NOT NULL,
  `imagefile` text NOT NULL,
  `htmlfile` text NOT NULL,
  `titlecache` text NOT NULL,
  `filehash` varchar(40) NOT NULL DEFAULT '',
  `warncount` int(11) NOT NULL DEFAULT '0',
  `config` text NOT NULL,
  `thumb_width` int(11) NOT NULL DEFAULT '0',
  `thumb_height` int(11) NOT NULL DEFAULT '0',
  `schedule` varchar(32) NOT NULL DEFAULT '*',
  `archiving` set('on','off') NOT NULL DEFAULT 'off',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `weathermap_data`
--

DROP TABLE IF EXISTS `weathermap_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weathermap_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rrdfile` varchar(255) NOT NULL,
  `data_source_name` varchar(19) NOT NULL,
  `last_time` int(11) NOT NULL,
  `last_value` varchar(255) NOT NULL,
  `last_calc` varchar(255) NOT NULL,
  `sequence` int(11) NOT NULL,
  `local_data_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rrdfile` (`rrdfile`),
  KEY `local_data_id` (`local_data_id`),
  KEY `data_source_name` (`data_source_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `weathermap_auth`
--

DROP TABLE IF EXISTS `weathermap_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weathermap_auth` (
  `userid` mediumint(9) NOT NULL DEFAULT '0',
  `mapid` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `weathermap_groups`
--

DROP TABLE IF EXISTS `weathermap_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weathermap_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `sortorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `weathermap_settings`
--

DROP TABLE IF EXISTS `weathermap_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weathermap_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mapid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `optname` varchar(128) NOT NULL DEFAULT '',
  `optvalue` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-06-21 21:15:50
