<?php

// uncomment the following lines and edit the values to suit your installation
define('MYSQL_HOST', 'localhost');
define('MYSQL_BENUTZER', 'bengel'); // database user name
define('MYSQL_KENNWORT', 'a6a273733aae26a47257952d30ab8c88');
define('MYSQL_DATENBANK', 'bengelsystem');
define('LOGFILE', "/var/log/dropamsee/orgatreffen");
define('EVENTNAME', "Orgatreffen");
define('INFORMATIONS_URL', "https://anmeldung.kleinkunst-ka.de/orgakon2024/");
define('SECRET_KEY', "irgendwasZufaelligesblubb"); // emails, siehe unten
define('SECRET_VERIFICATION', "irgendwasanderes,iblubbbstegalwas"); // emails, siehe unten
define('URLPREFIX', "https://www.orgatreff.jugglingpatterns.de/Orgatreffen/UrlLogin.php"); //  url for url logins

define('TAGE_DAUER', 4);

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, "de_DE.UTF-8");
$start_date = new DateTimeImmutable("2023-05-18");


// Geheimer Schlüssel für die Verschlüsselung von Tokens
// das ist das Passwort, mit dem sich aus den Tokens auch wieder der Username/Email extrahieren lässt
$secret_key = SECRET_KEY;
// Geheimes Wort, das an die Email angehaengt wird, um zu ueberpruefen, dass die Addresse nicht abgeschnitten ist
// das Wort darf kein "|" enthalten, das wird als Trenner verwendet
$secret_verification = SECRET_VERIFICATION;
// urlprefix: https Addresse des php Scripts, das die Tokens empfaengt und einen Account anlegt
$urlprefix = URLPREFIX;
