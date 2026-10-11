<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$pagename = "Admin Diensteübersicht";
$db_link = ConnectDB();
require '_login.php';
require_once '_functions.php';

// Nur für Admins
if ($AdminStatus != 1) {
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}

DatenbankAufDeutsch($db_link);

$message = "";

// ============================================================================
// POST-Verarbeitung (nur was zur Übersichtstabelle selbst gehört -- Dienst-/
// Schicht-Formulare mit ihrer eigenen Verarbeitung sind nach
// AdminDiensteLegacy.php ausgelagert)
// ============================================================================
-// This shouldnt need to transmit HelferID
HelferLevelAnzeigeCheckPOST($db_link, $HelferID, $AdminStatus, $AdminID);

// DELETE aus der Übersichtstabelle (admin_edit-Modus)
if (isset($_POST['DeleteSchichtID']) && (int)$_POST['DeleteSchichtID'] > 0) {
    $delID = (int)$_POST['DeleteSchichtID'];
    if (!DeleteSchicht($db_link, $delID, false)) {
        $message .= "Schicht {$delID} konnte nicht gelöscht werden (noch Helfer eingetragen?)<br>";
    } else {
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }
}

// Suchfilter aus GET/POST
$suchfilter = trim($_GET['suche'] ?? $_POST['suche'] ?? '');

// ============================================================================
// HTML-Ausgabe
// ============================================================================

echo PageHeader($pagename);
?>
<div style="width:100%">

<button class="back" onclick="window.location.href='Admin.php';"><b>&larrhk;</b></button>
<button type="button" onclick="window.location.href='AdminDiensteLegacy.php';">
  Formulare (Dienst/Schicht anlegen, mehrere Schichten in Serie)
</button>
<?php if ($message) { echo '<div class="error">' . $message . '</div>'; } ?>

<!-- ======================================================
     Übersichtstabelle aller Dienste/Schichten
     ====================================================== -->
<h3>Übersicht</h3>

<!-- Übersichtstabelle: nutzt ZeigeDiensteUndSchichten im admin_edit-Modus -->
<?php
ZeigeDiensteUndSchichten($db_link, $HelferID, [
    'modus'               => 'admin_edit',
    'zeitbereich'         => true,
    'helferlevel_auswahl' => true,
    'helferlevel_tabelle' => true,
    'meine_schichten_link'=> 'AdminMeineSchichten.php',
    'meine_schichten_name'=> $HelferName,
    'zeigeHierarchie'     => true,
    'suchfilter'          => $suchfilter,
    'HelferLevel'         => $HelferLevel,
    'AdminStatus'         => $AdminStatus,
    'AdminID'             => $AdminID,
]);
?>

<hr>

<button class="back" onclick="window.location.href='Admin.php';"><b>&larrhk;</b></button>
</div>
</body>
</html>
