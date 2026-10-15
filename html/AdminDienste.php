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
        $d = SchichtFormularLesen($f);
        if ($d['fehler']) {
            $message .= "Schicht {$id}: {$d['fehler']}<br>";
        } elseif (ChangeSchicht($db_link, $id, $d['von'], $d['bis'], $d['soll'], $d['dauer']) === false) {
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

// Nachfolgeschicht (Plus im Editiermodus einer Schicht): neue Schicht direkt im Anschluss an die
// gespeicherte Schicht, gleiche Dauer und Anzahl -- wie "Anschlussschicht vorbereiten" im Formular.
// Die Werte kommen aus der DB, nicht aus den (evtl. gerade geänderten, ungespeicherten) Feldern.
if (isset($_POST['NachfolgeSchicht'])) {
    $id = (int)$_POST['NachfolgeSchicht'];
    $s  = $id > 0 ? GetSchichtEinzeln($db_link, $id) : null;
    $log("NachfolgeSchicht von {$id}: " . ($s ? json_encode($s) : 'nicht gefunden'));
    if (!$s) {
        $message .= "Schicht {$id} gibt es nicht.<br>";
    } else {
        $aktiverDienst = (int)$s['DienstID'];
        $w = NachfolgeSchichtWerte($s['Von'], $s['Bis'], $s['Dauer'], $s['Soll']);
        if (strtotime($w['bis']) <= strtotime($w['von'])) {
            $message .= "Nachfolgeschicht von {$id}: Dauer ist 0 und lässt sich nicht aus Beginn/Ende ableiten.<br>";
        } elseif (NewSchicht($db_link, $aktiverDienst, $w['von'], $w['bis'], $w['soll'], $w['dauer'], $HelferName) === false) {
            $message .= "Die Nachfolgeschicht konnte nicht angelegt werden.<br>";
        } else {
            header("Location: " . UrlMitParametern($_SERVER['REQUEST_URI'], ['aktiv' => $aktiverDienst], ['loeschen']));
            exit;
        }
    }
}

// Neue Schicht (leere Zeile am Ende der Schichten eines Dienstes)
if (isset($_POST['NeueSchicht'])) {
    $dienst = (int)$_POST['NeueSchicht'];
    $f      = $_POST['neue_schicht'][$dienst] ?? null;
    $log("NeueSchicht Dienst={$dienst}, Felder " . (is_array($f) ? json_encode($f) : 'FEHLEN'));
    if ($dienst > 0 && is_array($f)) {
        $aktiverDienst = $dienst;   // Dienst bleibt aufgeklappt (auch bei Fehlermeldung)
        $d = SchichtFormularLesen($f);
        if (!GetEinzelDienst($db_link, $dienst)) {
            $message .= "Dienst {$dienst} gibt es nicht.<br>";
        } elseif ($d['fehler']) {
            $message .= "Neue Schicht: {$d['fehler']}<br>";
        } elseif (NewSchicht($db_link, $dienst, $d['von'], $d['bis'], $d['soll'], $d['dauer'], $HelferName) === false) {
            $message .= "Die neue Schicht konnte nicht angelegt werden.<br>";
        } else {
            $log("Schicht für Dienst {$dienst} angelegt");
            header("Location: " . UrlMitParametern($_SERVER['REQUEST_URI'], ['aktiv' => $dienst], ['loeschen']));
            exit;
        }
    } else {
        $message .= "Neue Schicht: Die Formulardaten sind nicht beim Server angekommen, nichts gespeichert.<br>";
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

// Übersichts-URL (Suche, Zeitbereich, ...) für den Zurück-Pfeil der Formularseite merken. Kommt vom
// Server selbst, nicht vom Client -- die Formularseite braucht dadurch keinen Parameter und keine Prüfung.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['AdminDiensteUrl'] = UrlMitParametern($_SERVER['REQUEST_URI'], [], ['aktiv', 'loeschen']);
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
