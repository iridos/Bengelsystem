/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: drophelfer2025dev
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `Dienst`
--

DROP TABLE IF EXISTS `Dienst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Dienst` (
  `DienstID` int(11) NOT NULL AUTO_INCREMENT,
  `Was` text CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `Wo` text CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `Info` text CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `Leiter` int(11) NOT NULL,
  `ElternDienstID` int(11) DEFAULT NULL,
  `HelferLevel` int(11) DEFAULT NULL,
  PRIMARY KEY (`DienstID`),
  KEY `fk_dienst_helferlevel` (`HelferLevel`),
  CONSTRAINT `fk_dienst_helferlevel` FOREIGN KEY (`HelferLevel`) REFERENCES `HelferLevel` (`HelferLevel`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `EinzelSchicht`
--

DROP TABLE IF EXISTS `EinzelSchicht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `EinzelSchicht` (
  `EinzelSchichtID` int(11) NOT NULL AUTO_INCREMENT,
  `SchichtID` int(11) NOT NULL,
  `HelferID` int(11) NOT NULL,
  PRIMARY KEY (`EinzelSchichtID`)
) ENGINE=InnoDB AUTO_INCREMENT=1346 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Helfer`
--

DROP TABLE IF EXISTS `Helfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Helfer` (
  `HelferId` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `Status` int(11) NOT NULL,
  `Email` varchar(50) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `Handy` varchar(50) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `BildFile` varchar(200) CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  `DoReport` tinyint(1) NOT NULL,
  `Admin` int(11) DEFAULT 0,
  `Passwort` varchar(200) DEFAULT NULL,
  `HelferLevel` int(11) DEFAULT NULL,
  PRIMARY KEY (`HelferId`),
  UNIQUE KEY `unique_index_email` (`Email`),
  KEY `fk_helferlevel` (`HelferLevel`),
  CONSTRAINT `fk_helferlevel` FOREIGN KEY (`HelferLevel`) REFERENCES `HelferLevel` (`HelferLevel`)
) ENGINE=InnoDB AUTO_INCREMENT=521 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HelferLevel`
--

DROP TABLE IF EXISTS `HelferLevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `HelferLevel` (
  `HelferLevel` int(11) NOT NULL AUTO_INCREMENT,
  `HelferLevelBeschreibung` varchar(255) DEFAULT NULL,
  `linkcode` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`HelferLevel`),
  UNIQUE KEY `linkcode` (`linkcode`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Schicht`
--

DROP TABLE IF EXISTS `Schicht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Schicht` (
  `SchichtID` int(11) NOT NULL AUTO_INCREMENT,
  `DienstID` int(11) NOT NULL,
  `Von` datetime NOT NULL,
  `Bis` datetime NOT NULL,
  `Soll` int(11) NOT NULL,
  `Dauer` time DEFAULT NULL,
  PRIMARY KEY (`SchichtID`)
) ENGINE=InnoDB AUTO_INCREMENT=273 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `SchichtUebersicht`
--

DROP TABLE IF EXISTS `SchichtUebersicht`;
/*!50001 DROP VIEW IF EXISTS `SchichtUebersicht`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `SchichtUebersicht` AS SELECT
 1 AS `DienstID`,
  1 AS `SchichtID`,
  1 AS `Von`,
  1 AS `Bis`,
  1 AS `C`,
  1 AS `Soll` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `Status`
--

DROP TABLE IF EXISTS `Status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Status` (
  `StatusID` int(11) NOT NULL AUTO_INCREMENT,
  `Text` text CHARACTER SET latin1 COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`StatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `SchichtUebersicht`
--

/*!50001 DROP VIEW IF EXISTS `SchichtUebersicht`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `SchichtUebersicht` AS select `Schicht`.`DienstID` AS `DienstID`,`Schicht`.`SchichtID` AS `SchichtID`,`Schicht`.`Von` AS `Von`,`Schicht`.`Bis` AS `Bis`,count(`EinzelSchicht`.`SchichtID`) AS `C`,`Schicht`.`Soll` AS `Soll` from (`Schicht` left join `EinzelSchicht` on(`Schicht`.`SchichtID` = `EinzelSchicht`.`SchichtID`)) group by `Schicht`.`SchichtID` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-23 13:44:45
