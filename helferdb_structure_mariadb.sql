DROP TABLE IF EXISTS `Dienst`;
CREATE TABLE `Dienst` (
  `DienstID` int(11) NOT NULL AUTO_INCREMENT,
  `Was` text NOT NULL,
  `Wo` text NOT NULL,
  `Info` text NOT NULL,
  `Leiter` int(11) NOT NULL,
  `ElternDienstID` int(11) DEFAULT NULL,
  `HelferLevel` int(11) DEFAULT NULL,
  PRIMARY KEY (`DienstID`)
);
DROP TABLE IF EXISTS `EinzelSchicht`;
CREATE TABLE `EinzelSchicht` (
  `EinzelSchichtID` int(11) NOT NULL AUTO_INCREMENT,
  `SchichtID` int(11) NOT NULL,
  `HelferID` int(11) NOT NULL,
  PRIMARY KEY (`EinzelSchichtID`)
);
DROP TABLE IF EXISTS `Helfer`;
CREATE TABLE `Helfer` (
  `HelferId` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  `Status` int(11) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Handy` varchar(50) NOT NULL,
  `BildFile` varchar(200) NOT NULL,
  `DoReport` tinyint(1) NOT NULL,
  `Admin` int(11) DEFAULT 0,
  `Passwort` varchar(200) DEFAULT NULL,
  `HelferLevel` int(11) DEFAULT NULL,
  PRIMARY KEY (`HelferId`),
  UNIQUE KEY `unique_index_email` (`Email`)
);
DROP TABLE IF EXISTS `HelferLevel`;
CREATE TABLE `HelferLevel` (
  `HelferLevel` int(11) DEFAULT NULL,
  `HelferLevelBeschreibung` varchar(255) DEFAULT NULL
);
INSERT INTO HelferLevel(HelferLevel, HelferLevelBeschreibung)
VALUES (1,'Orga');
INSERT INTO HelferLevel(HelferLevel, HelferLevelBeschreibung)
VALUES (2,'Teilnehmer');
DROP TABLE IF EXISTS `Schicht`;
CREATE TABLE `Schicht` (
  `SchichtID` int(11) NOT NULL AUTO_INCREMENT,
  `DienstID` int(11) NOT NULL,
  `Von` datetime NOT NULL,
  `Bis` datetime NOT NULL,
  `Soll` int(11) NOT NULL,
  `Dauer` time DEFAULT NULL,
  PRIMARY KEY (`SchichtID`)
);
DROP TABLE IF EXISTS `Status`;
CREATE TABLE `Status` (
  `StatusID` int(11) NOT NULL AUTO_INCREMENT,
  `Text` text NOT NULL,
  PRIMARY KEY (`StatusID`)
);
DROP VIEW IF EXISTS `SchichtUebersicht`;
CREATE VIEW `SchichtUebersicht` AS SELECT
   `Schicht`.`DienstID` AS `DienstID`,
   `Schicht`.`SchichtID` AS `SchichtID`,
   `Schicht`.`Von` AS `Von`,`Schicht`.
   `Bis` AS `Bis`,
   count(`EinzelSchicht`.`SchichtID`) AS `C`,
   `Schicht`.`Soll` AS `Soll` FROM (`Schicht` LEFT JOIN `EinzelSchicht` ON(`Schicht`.`SchichtID` = `EinzelSchicht`.`SchichtID`)) GROUP BY `Schicht`.`SchichtID`;
