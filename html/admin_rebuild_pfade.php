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
echo PageHeader('Dienst-Pfade neu aufbauen', EVENTNAME);
echo TableHeader('Dienst-Pfade neu aufbauen', 'AdminDienste.php', EVENTNAME, 'Zur&uuml;ck');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rebuild'])) {
    echo '<p>Baue alle Dienst-Pfade neu auf...</p>';
    
    $result = RebuildAlleDienstPfade($db_link);
    
    if ($result === true) {
        echo '<p style="color: green; font-weight: bold;">Alle Dienst-Pfade wurden erfolgreich neu aufgebaut!</p>';
    } else {
        echo '<p style="color: red; font-weight: bold;">Fehler beim Neuaufbau der Dienst-Pfade!</p>';
    }
}

echo '<form method="post">';
echo '<p><button type="submit" name="rebuild" style="padding: 10px 20px; font-size: 16px;">Dienst-Pfade neu aufbauen</button></p>';
echo '<p><small>Diese Funktion repariert alle Dienst-Baum-Pfade und löst Zyklen auf, indem betroffene Dienste auf Top-Level gesetzt werden.</small></p>';
echo '</form>';

$db_link->close();

echo '</body></html>';
