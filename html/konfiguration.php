 
<?php
// create the file below from the template:
require_once  __DIR__ . '/../bengelsystem_konfiguration.php';

// muss nicht angepasst werden // no changes needed
// Zeitbereich: -1 davor, 0 kein Limit, 1-N Tag N der Con, 1000: nach der Con
$ZeitBereichWerte = range(-1, TAGE_DAUER);
array_push($ZeitBereichWerte, 1000);
define('ZEITBEREICHWERTE', $ZeitBereichWerte);
$TageNamenDeutsch = array("So","Mo","Di","Mi","Do","Fr","Sa");

//Kalender-Konfiguration
$dsn = "mysql:host=localhost;dbname=" . MYSQL_DATENBANK; // dsn fuer Kalender
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
);
