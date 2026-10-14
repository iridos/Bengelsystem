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
// Aufklappen kommt aus der URL (nicht aus der Session): nach dem Speichern/Löschen leitet der
// Server auf dieselbe Seite mit ?aktiv=<DienstID> um. An der Adresszeile sieht man sofort, ob
// die Aktion beim Server angekommen ist.
// Debuggen: Seite mit ?debug=1 aufrufen -> Zusatzinfos auf der Seite und Zeilen im PHP-Error-Log.
$aktiverDienst = (int)($_GET['aktiv'] ?? 0) ?: null;
$loeschenAktiv = !empty($_GET['loeschen']);
$debug = isset($_GET['debug']);
$log = function (string $text) use ($debug) { if ($debug) { error_log("AdminDienste DEBUG: " . $text); } };
if ($debug && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $log("POST mit " . count($_POST) . " Variablen, Schlüssel: " . implode(',', array_slice(array_keys($_POST), 0, 25)));
}

// Inline-Speichern einer Schicht (Bleistift in der Übersichtstabelle)
if (isset($_POST['SpeicherSchicht'])) {
    $id = (int)$_POST['SpeicherSchicht'];
    $f  = $_POST['edit_schicht'][$id] ?? null;
    $log("SpeicherSchicht id={$id}, Felder " . (is_array($f) ? json_encode($f) : 'FEHLEN'));
    if ($id > 0 && is_array($f)) {
        $aktiverDienst = GetDienstIDVonSchicht($db_link, $id);   // Dienst bleibt aufgeklappt (auch bei Fehlermeldung)
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
            $log("Schicht {$id} gespeichert, Redirect mit aktiv={$aktiverDienst}");
            header("Location: " . UrlMitParametern($_SERVER['REQUEST_URI'], ['aktiv' => $aktiverDienst], ['loeschen']));
            exit;
        }
    } else {
        $message .= "Schicht {$id}: Die Formulardaten sind nicht beim Server angekommen, nichts gespeichert.<br>";
    }
}

// Inline-Speichern eines Dienstes
if (isset($_POST['SpeicherDienst'])) {
    $id = (int)$_POST['SpeicherDienst'];
    $f  = $_POST['edit_dienst'][$id] ?? null;
    $log("SpeicherDienst id={$id}, Felder " . (is_array($f) ? json_encode($f) : 'FEHLEN'));
    if ($id > 0 && is_array($f)) {
        $aktiverDienst = $id;   // bearbeiteter Dienst bleibt aufgeklappt (auch bei Fehlermeldung)
        $was    = trim($f['was'] ?? '');
        $leiter = (int)($f['leiter'] ?? 0);          // "12 - Name" -> 12
        $leiter = $leiter > 0 ? $leiter : null;
        if ($was === '') {
            $message .= "Dienst {$id}: 'Was' darf nicht leer sein.<br>";
        } elseif (ChangeDienst($db_link, $id, $was, $f['wo'] ?? '', $f['info'] ?? '',
                               $leiter, (int)($f['eltern'] ?? 0), (int)($f['level'] ?? 0)) === false) {
            $message .= "Dienst {$id} konnte nicht gespeichert werden (Elterndienst darf nicht der Dienst selbst oder einer seiner Unterdienste sein).<br>";
        } else {
            $log("Dienst {$id} gespeichert, Redirect mit aktiv={$id}");
            header("Location: " . UrlMitParametern($_SERVER['REQUEST_URI'], ['aktiv' => $id], ['loeschen']));
            exit;
        }
    } else {
        $message .= "Dienst {$id}: Die Formulardaten sind nicht beim Server angekommen, nichts gespeichert.<br>";
    }
}

// DELETE aus der Übersichtstabelle (admin_edit-Modus)
if (isset($_POST['DeleteSchichtID']) && (int)$_POST['DeleteSchichtID'] > 0) {
    $delID = (int)$_POST['DeleteSchichtID'];
    $aktiverDienst = GetDienstIDVonSchicht($db_link, $delID);   // vor dem Löschen merken
    $loeschenAktiv = true;                                      // weitere Schichten direkt löschbar lassen
    $log("DeleteSchichtID={$delID}, Dienst {$aktiverDienst}");
    if (!DeleteSchicht($db_link, $delID, false)) {
        $message .= "Schicht {$delID} konnte nicht gelöscht werden (noch Helfer eingetragen?)<br>";
    } else {
        // REQUEST_URI statt PHP_SELF: Suchfilter etc. bleiben erhalten
        header("Location: " . UrlMitParametern($_SERVER['REQUEST_URI'], ['aktiv' => $aktiverDienst, 'loeschen' => 1]));
        exit;
    }
}

// HelferLevel-Umschaltung: MUSS NACH den Speichern/Löschen-Handlern stehen. Das HelferLevel-Dropdown
// liegt im selben <form> wie die Tabelle, "helfer-level-anzeige" steckt also in jedem POST aus dieser
// Tabelle -- die Funktion leitet bei jedem POST um, in dem es vorkommt, und hätte so jedes Speichern
// abgefangen, bevor ein Handler es sieht.
HelferLevelAnzeigeCheckPOST($db_link, $HelferID, $AdminStatus, $AdminID);

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
<?php if ($debug) {
    echo '<pre style="background:#ffd;border:1px solid #cc0;padding:4px;font-size:smaller">DEBUG '
       . htmlspecialchars($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']
       . "\nPOST-Variablen: " . count($_POST) . (count($_POST) ? ' (' . implode(', ', array_slice(array_keys($_POST), 0, 15)) . ')' : '')
       . "\naktiverDienst=" . var_export($aktiverDienst, true) . ' loeschenAktiv=' . var_export($loeschenAktiv, true))
       . '</pre>';
} ?>

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
    'aktiverDienst'       => $aktiverDienst,
    'loeschenAktiv'       => $loeschenAktiv,
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
