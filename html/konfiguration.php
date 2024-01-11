 
<?php
// datenbank-defines extern
require_once '/etc/helferdb_konfiguration.php';
// die folgenden Zeilen ohne Kommentarzeichen nach /etc/helferdb_konfiguration.php
// kopieren und an die eigene Datenbank anpassen
// define( 'MYSQL_HOST', 'localhost' );
// define( 'MYSQL_BENUTZER', 'benutzername_der_datenbank' );
// define( 'MYSQL_KENNWORT', 'passwort_des_db_nutzers' );
// define( 'MYSQL_DATENBANK', 'name_der_datenbank' );
// define( 'LOGFILE', "/pfad/zu/einer/www-schreibbaren/datei");
define( 'EVENTNAME', "dubbelcon");
// define( 'INFORMATIONS_URL', "http://drop-am-see.de/Gelaende");

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, "de_DE.UTF-8");
$start_date = new DateTimeImmutable("2023-05-18");
define('TAGE_DAUER', 4);

// Geheimer Schlüssel für die Verschlüsselung von Tokens
// das ist das Passwort, mit dem sich aus den Tokens auch wieder der Username/Email extrahieren lässt

// $secret_key = "irgendwasZufaelliges";
// Geheimes Wort, das an die Email angehaengt wird, um zu ueberpruefen, dass die Addresse nicht abgeschnitten ist
// das Wort darf kein "|" enthalten, das wird als Trenner verwendet

// $secret_verification = "irgendwasanderes,istegalwas";
// urlprefix: https Addresse des php Scripts, das die Tokens empfaengt und einen Account anlegt
// volle URL, da sie u.a. per Email ersetzt wird

// $urlprefix="https://meinserver.de/2023dev/UrlLogin.php";




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
