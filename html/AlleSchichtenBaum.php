<?php

require_once 'konfiguration.php';
require_once 'SQL.php';
require_once '_functions.php';

session_start();

if (!isset($_SESSION['HelferID'])) {
    header('Location: index.php');
    exit;
}

$db_link = ConnectDB();

// Page Header
$HelferID = $_SESSION['HelferID'];
echo PageHeader('Alle Schichten (Baumansicht)', EVENTNAME);

// Back Link
echo TableHeader('Alle Schichten (Baumansicht)', 'index.php', EVENTNAME, 'Zur&uuml;ck');

// Hauptinhalt
$opts = [
    'modus' => 'SchichtEintragen',
    'zeitbereich' => true,
    'helferlevel_auswahl' => true,
    'helferlevel_tabelle' => true,
    'meine_schichten_link' => 'MeineSchichten.php',
];

ZeigeDienstEbene($db_link, $HelferID, $opts);

$db_link->close();

echo '</body></html>';
