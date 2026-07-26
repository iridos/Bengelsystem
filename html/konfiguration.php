<?php

// Datenbank-Konfiguration
define('MYSQL_HOST',     'localhost');
define('MYSQL_BENUTZER', 'bengel_user');
define('MYSQL_KENNWORT', 'bengel_password');
define('MYSQL_DATENBANK', 'Bengelsystem');

// Event-Name
define('EVENTNAME', 'EJC');

// jQuery URL
define('JQUERY', 'https://code.jquery.com/jquery-3.6.0.min.js');

// Logdatei
define('LOGFILE', '/var/log/bengelsystem.log');

// Hierarchie-Konfiguration
define('DIENST_TEILBAUM_LIMIT', 10000);
define('DIENST_FLATTEN_SCHWELLWERT', 50);

// Session-Einstellungen
session_start();

// Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zeitbereich Standardwerte
$start_date = '2000-01-01';
$ZeitBereich = 30;
$TageNamenDeutsch = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');

// Datenbank auf Deutsch setzen
function DatenbankAufDeutsch($db_link) {
    mysqli_query($db_link, "SET lc_time_names = 'de_DE'");
    mysqli_query($db_link, "SET NAMES utf8mb4");
}

// Alle HelferLevel
function alleHelferLevel($db_link) {
    return array(
        1 => 'Team',
        2 => 'Orga',
        3 => 'Dauerhelfer',
        4 => 'Leitung'
    );
}
