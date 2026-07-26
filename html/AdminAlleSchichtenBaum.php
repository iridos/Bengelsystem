<?php

require_once 'konfiguration.php';
require_once 'SQL.php';
require_once '_functions.php';

session_start();

if (!isset($_SESSION['AdminStatus']) || $_SESSION['AdminStatus'] != 1) {
    header('Location: index.php');
    exit;
}

$db_link = ConnectDB();

// Page Header
echo PageHeader('Alle Schichten (Baumansicht - Admin)', EVENTNAME);
echo TableHeader('Alle Schichten (Baumansicht - Admin)', 'Admin.php', EVENTNAME, 'Zur&uuml;ck');

// Hauptinhalt
$opts = [
    'modus' => 'admin_edit',
    'zeitbereich' => true,
    'helferlevel_auswahl' => true,
    'helferlevel_tabelle' => true,
    'meine_schichten_link' => 'AdminMeineSchichten.php',
    'AdminStatus' => 1,
    'AdminID' => $_SESSION['AdminID'] ?? 0,
];

ZeigeDienstEbene($db_link, $_SESSION['HelferID'] ?? 0, $opts);

$db_link->close();

echo '</body></html>';
