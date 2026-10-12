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
HelferLevelAnzeigeCheckPOST($db_link, $HelferID, $AdminStatus, $AdminID);

// Inline-Speichern einer Schicht (Bleistift in der Übersichtstabelle)
if (isset($_POST['SpeicherSchicht'])) {
    $id = (int)$_POST['SpeicherSchicht'];
    $f  = $_POST['edit_schicht'][$id] ?? null;
    if ($id > 0 && is_array($f)) {
        $von   = str_replace('T', ' ', trim($f['von'] ?? ''));
        $bis   = str_replace('T', ' ', trim($f['bis'] ?? ''));
        $soll  = (int)($f['soll'] ?? 0);
        $dauer = DauerAusFormular($f['dauer_h'] ?? 0, $f['dauer_m'] ?? 0);
        if ($von === '' || $bis === '' || strtotime($bis) <= strtotime($von)) {
            $message .= "Schicht {$id}: Das Ende muss nach dem Beginn liegen.<br>";
        } elseif ($soll < 1) {
            $message .= "Schicht {$id}: Die Anzahl (Soll) muss mindestens 1 sein.<br>";
        } elseif (ChangeSchicht($db_link, $id, $von, $bis, $soll, $dauer) === false) {
            $message .= "Schicht {$id} konnte nicht gespeichert werden.<br>";
        } else {
            // vorhandener Mechanismus: Zeile wird aufgeklappt und angesprungen
            $_SESSION['SchichtIdAktiv'] = $id;
            header("Location: " . $_SERVER['REQUEST_URI']); exit;
        }
    }
}

// Inline-Speichern eines Dienstes
if (isset($_POST['SpeicherDienst'])) {
    $id = (int)$_POST['SpeicherDienst'];
    $f  = $_POST['edit_dienst'][$id] ?? null;
    if ($id > 0 && is_array($f)) {
        $was    = trim($f['was'] ?? '');
        $leiter = (int)($f['leiter'] ?? 0);          // "12 - Name" -> 12
        $leiter = $leiter > 0 ? $leiter : null;
        if ($was === '') {
            $message .= "Dienst {$id}: 'Was' darf nicht leer sein.<br>";
        } elseif (ChangeDienst($db_link, $id, $was, $f['wo'] ?? '', $f['info'] ?? '',
                               $leiter, (int)($f['eltern'] ?? 0), (int)($f['level'] ?? 0)) === false) {
            $message .= "Dienst {$id} konnte nicht gespeichert werden (Elterndienst darf nicht der Dienst selbst oder einer seiner Unterdienste sein).<br>";
        } else {
            header("Location: " . $_SERVER['REQUEST_URI'] . "#D" . $id); exit;
        }
    }
}

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
