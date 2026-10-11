<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$pagename = "Admin Diensteübersicht";
$db_link = ConnectDB();
require '_login.php';
require_once '_functions.php';
//require_once '_schichten_view.php';

// Nur für Admins
if ($AdminStatus != 1) {
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}

DatenbankAufDeutsch($db_link);

// ============================================================================
// Session-Werte lesen
// ============================================================================
$message  = "";
$DienstID = $_SESSION['DienstID'] ?? null;
$SchichtID = $_SESSION['SchichtID'] ?? null;

// ============================================================================
// POST-Verarbeitung
// ============================================================================
HelferLevelAnzeigeCheckPOST($db_link,$ZielHelferID,$AdminStatus,$AdminID);
if (isset($_POST['ChangeDienst'])) {
    ChangeDienst(
        $db_link, $DienstID,
        $_POST['Dienst-Was'], $_POST['Dienst-Wo'], $_POST['Dienst-Info'],
        $_POST['Dienst-Leiter'], $_POST['Dienst-Gruppe'], $_POST['HelferLevel']
    );
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

if (isset($_POST['NewDienst'])) {
    $Gruppe = $_POST['Dienst-Gruppe'] ?? NULL; // NULL = root-Dienste
    $neueID = NewDienst(
        $db_link,
        $_POST['Dienst-Was'], $_POST['Dienst-Wo'], $_POST['Dienst-Info'],
        $_POST['Dienst-Leiter'], $Gruppe, $_POST['HelferLevel']
    );
    $_SESSION['DienstID'] = $neueID;
    error_log("lege dienst $neueID an");
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

if (isset($_POST['DeleteDienst'])) {
    if (!DeleteDienst($db_link, $DienstID, false)) {
        $message .= "Erst Schichten des Dienstes löschen!";
    } else {
        $DienstID = null;
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }
}

if (isset($_POST['ChangeSchicht'])) {
    ChangeSchicht(
        $db_link, $SchichtID,
        $_POST['Schicht-Von'], $_POST['Schicht-Bis'],
        $_POST['Schicht-Soll'], $_POST['Schicht-Dauer']
    );
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// Checkbox "Endzeit von Dauer" / "Anschlussschicht vorbereiten" 
// entweder aus POST direkt oder aus Session Variable
if (isset($_POST['Schicht-Von'])) {
    $AutomaticBis      = isset($_POST['Schicht-Automatic-Bis'])   ? 1 : 0;
    $Anschlussschicht  = isset($_POST['Schicht-Anschlussschicht']) ? 1 : 0;
    $_SESSION['Schicht-Automatic-Bis']    = $AutomaticBis;
    $_SESSION['Schicht-Anschlussschicht'] = $Anschlussschicht;
} else {
    $AutomaticBis     = $_SESSION['Schicht-Automatic-Bis']    ?? 0;
    $Anschlussschicht = $_SESSION['Schicht-Anschlussschicht'] ?? 0;
}

if (isset($_POST['NewSchicht'])) {
    $Von   = $_POST['Schicht-Von'];
    $Bis   = $_POST['Schicht-Bis'];
    $Soll  = $_POST['Schicht-Soll'];
    $Dauer = $_POST['Schicht-Dauer'];
    if ($AutomaticBis) {
        $Temp  = new DateTime($Von);
        $Temp2 = DateInterval::createFromDateString($Dauer[0] . $Dauer[1] . ' hours ' . $Dauer[3] . $Dauer[4] . ' minutes');
        $Bis   = $Temp->add($Temp2)->format('Y-m-d H:i:s');
    }
    NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll, $Dauer, $HelferName);
    $_SESSION['SchichtID'] = LastInsertId($db_link);
    // Anschlussschicht: die nächste Schicht soll direkt an diese anschließen
    // (Von = Bis der gerade angelegten Schicht). Muss hier vor dem Redirect
    // gemerkt werden -- der Check weiter unten im Schicht-Auswahl-Loop kann nie
    // greifen, weil dieser Zweig hier immer per exit endet, bevor die Anzeige
    // erreicht wird. DateTime normalisiert das Format unabhängig davon, ob $Bis
    // gerade per AutomaticBis berechnet oder roh aus dem datetime-local-Feld kam.
    $_SESSION['Schicht-AnschlussVon'] = $Anschlussschicht ? (new DateTime($Bis))->format('Y-m-d H:i:s') : null;
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

if (isset($_POST['DeleteSchicht'])) {
    if (!DeleteSchicht($db_link, $SchichtID, false)) {
        $message .= "Erst Schicht leeren<br>";
    } else {
        $SchichtID = 0;
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

if (isset($_POST['ShowSchichten'])) { $DienstID = (int)$_POST['DienstSearch']; }
if (isset($_POST['DienstSearch']))  {
    $DienstID  = (int)$_POST['DienstSearch'];
    $SchichtID = 0;
}
if (isset($_POST['ShowSchicht']))   { $SchichtID = $_POST['SchichtSearch']; }
if (isset($_POST['SchichtSearch']) && !isset($_POST['NewSchicht']) && !isset($_POST['DeleteSchicht'])) {
    $SchichtID = $_POST['SchichtSearch'];
}

// GET: Dienst oder Schicht direkt aufrufen (z. B. aus der Übersichtstabelle)
if (isset($_GET['DienstID']))  { $DienstID  = (int)$_GET['DienstID'];  }
if (isset($_GET['SchichtID'])) { $SchichtID = (int)$_GET['SchichtID']; }

// Suchfilter aus GET/POST
$suchfilter = trim($_GET['suche'] ?? $_POST['suche'] ?? '');

// Session persistieren
$_SESSION['DienstID']  = $DienstID;
$_SESSION['SchichtID'] = $SchichtID;

// ============================================================================
// Aktuellen Dienst laden
// ============================================================================
$Was = $Wo = $Info = $Leiter = $Gruppe = $HelferLevelDienst = "";
$AlleDienstNamen = [];

if ($DienstID) {
    $db_erg = GetDienste($db_link);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $AlleDienstNamen[$zeile['DienstID']] = $zeile['Was'];
        if ($zeile['DienstID'] == $DienstID) {
            $Was               = $zeile['Was'];
            $Wo                = $zeile['Wo'];
            $Info              = $zeile['Info'];
            $Leiter            = $zeile['Leiter'];
            $Gruppe            = $zeile['ElternDienstID'];
            $HelferLevelDienst = $zeile['HelferLevel'];
        }
    }
    mysqli_free_result($db_erg);
}

// ============================================================================
// HTML-Ausgabe
// ============================================================================

echo PageHeader($pagename);
?>
<div style="width:100%">

<button class="back" onclick="window.location.href='Admin.php';"><b>&larrhk;</b></button>
<?php if ($message) { echo '<div class="error">' . $message . '</div>'; } ?>

<!-- ======================================================
     ABSCHNITT 1: Dienst anlegen / bearbeiten
     ====================================================== -->
<h3>Dienst bearbeiten</h3>
<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
  <table border="0" class="commontable">
    <tr><th>Dienst</th>
    <th>
      <input type="text" name="DienstSearch" id="DienstSearch" list="dienst-search-datalist"
             placeholder="Dienst suchen…"
             value="<?= $DienstID ? (int)$DienstID . ' - ' . htmlspecialchars($AlleDienstNamen[$DienstID] ?? '?') : '' ?>"
             onchange="submit()">
      <datalist id="dienst-search-datalist">
<?php
$db_erg = GetDienste($db_link);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    echo "<option value='" . (int)$zeile['DienstID'] . " - " . htmlspecialchars($zeile['Was']) . "'>";
}
mysqli_free_result($db_erg);
?>
      </datalist>
    </th></tr>
  </table>

  <p><noscript><button name="ShowSchichten" value="1">Dienst anzeigen</button></noscript></p>

<?php if ($DienstID): ?>
  <table border="0" class="commontable collapsible">
    <tr class="header"><th><span>+</span> Details: <?= htmlspecialchars($Was ?? '') ?></th></tr>
    <tr class="collapsible-content"><td>Was</td></tr>
    <tr class="collapsible-content"><td><input name="Dienst-Was" type="text" value="<?= htmlspecialchars($Was ?? '') ?>"></td></tr>
    <tr class="collapsible-content"><td>Wo</td></tr>
    <tr class="collapsible-content"><td><input name="Dienst-Wo" type="text" value="<?= htmlspecialchars($Wo ?? '') ?>"></td></tr>
    <tr class="collapsible-content"><td>Info</td></tr>
    <tr class="collapsible-content"><td>
    <textarea name="Dienst-Info" rows="3"><?= htmlspecialchars($Info ?? '') ?></textarea>
    </td></tr>
    <tr class="collapsible-content"><td>Leiter</td></tr>
    <tr class="collapsible-content"><td>
      <select name="Dienst-Leiter">
<?php
$db_erg = HelferListe($db_link);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $sel = ($zeile['HelferID'] == $Leiter) ? "selected='selected'" : "";
    echo "<option value='" . $zeile['HelferID'] . "' {$sel}>"
       . htmlspecialchars($zeile['Name']) . "</option>";
}
mysqli_free_result($db_erg);
?>
      </select>
    </td></tr>
    <tr class="collapsible-content"><td>Gruppe</td></tr>
    <tr class="collapsible-content"><td>
      <input type="text" name="Dienst-Gruppe" list="dienste-datalist"
             placeholder="ID oder Name eingeben, leer = Top-Level"
             value="<?= $Gruppe ? (int)$Gruppe . ' - ' . htmlspecialchars($AlleDienstNamen[$Gruppe] ?? '?') : '' ?>">
      <datalist id="dienste-datalist">
<?php
$db_erg = GetDiensteAuswahlbar($db_link, $DienstID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    echo "<option value='" . (int)$zeile['DienstID'] . " - " . htmlspecialchars($zeile['Was']) . "'>";
}
?>
      </datalist>
    </td></tr>
    <tr class="collapsible-content"><td>HelferLevel</td></tr>
    <tr class="collapsible-content"><td>
      <select name="HelferLevel">
<?php
$alleHelferLevel = alleHelferLevel($db_link);
foreach ($alleHelferLevel as $lvl => $beschreibung) {
    $sel = ($lvl == $HelferLevelDienst) ? "selected" : "";
    echo "<option value='{$lvl}' {$sel}>" . htmlspecialchars($beschreibung) . "</option>";
}
?>
      </select>
    </td></tr>
    <tr class="collapsible-content">
      <td>
        <button name="NewDienst" value="1">Dienst anlegen</button>
        <button name="ChangeDienst" value="1">Ändern</button>
        <button name="DeleteDienst" value="1">Löschen</button>
      </td>
    </tr>
  </table>
<?php endif; ?>
</form>

<!-- ======================================================
     ABSCHNITT 2: Schicht anlegen / bearbeiten
     ====================================================== -->
<?php if ($DienstID): ?>
<h3>Schicht bearbeiten</h3>
<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
  <input type="hidden" name="DienstSearch" value="<?= (int)$DienstID ?>">
  <table border="0" class="commontable">
    <tr><th>Schicht</th>
    <th>
      <select name="SchichtSearch" id="SchichtSearch" onchange="submit()">
<?php
$Soll  = 1;
$Von   = $Bis = $Dauer = "";
$db_erg = GetSchichtenEinesDienstes($db_link, $DienstID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if (!$SchichtID) { $SchichtID = $zeile['SchichtID']; }
    $sel = ($zeile['SchichtID'] == $SchichtID) ? "selected='selected'" : "";
    if ($sel) {
        $Von   = $zeile['Von'];
        $Bis   = $zeile['Bis'];
        $Soll  = (int)$zeile['Soll'];
        $Dauer = $zeile['Dauer'];
    }
    echo "<option value='" . $zeile['SchichtID'] . "' {$sel}>"
       . htmlspecialchars("ID:".$zeile['SchichtID'] . " " . $zeile['TagVon']) . "</option>";
}
mysqli_free_result($db_erg);

// Anschlussschicht: direkt nach "Schicht anlegen" gesetzt (siehe NewSchicht-
// Verarbeitung oben). Einmalig anwenden und aus der Session löschen, damit es
// nicht bei der nächsten, unabhängigen Navigation (z.B. andere Schicht manuell
// ausgewählt) nochmal zuschlägt.
if (!empty($_SESSION['Schicht-AnschlussVon'])) {
    $Von = $_SESSION['Schicht-AnschlussVon'];
    if ($AutomaticBis && $Dauer) {
        $Temp  = new DateTime($Von);
        $Temp2 = DateInterval::createFromDateString($Dauer[0] . $Dauer[1] . ' hours ' . $Dauer[3] . $Dauer[4] . ' minutes');
        $Bis   = $Temp->add($Temp2)->format('Y-m-d H:i:s');
    }
}
unset($_SESSION['Schicht-AnschlussVon']);
?>
      </select>
    </th></tr>
  </table>
  <p><noscript><button name="ShowSchicht" value="1">Schicht anzeigen</button></noscript></p>

  <table border="0" class="commontable">
    <tr><td>Von</td></tr>
    <tr><td>
      <input id="Schicht-Von" name="Schicht-Von" type="datetime-local"
             onKeyUp="setEndDate()" value="<?= htmlspecialchars($Von ?? '') ?>" required>
    </td></tr>
    <tr><td>Dauer</td></tr>
    <tr><td>
      <input id="Schicht-Dauer" name="Schicht-Dauer" type="time"
             onChange="setEndDate()" value="<?= htmlspecialchars($Dauer ?? '01:00') ?>" required>
    </td></tr>
    <tr><td>Bis</td></tr>
    <tr><td>
      <input id="Schicht-Bis" name="Schicht-Bis" type="datetime-local"
             value="<?= htmlspecialchars($Bis ?? '') ?>" required>
    </td></tr>
    <tr><td>Anzahl (Soll)</td></tr>
    <tr><td>
      <input name="Schicht-Soll" type="number" min="1"
             value="<?= htmlspecialchars((string)(int)$Soll) ?>" required>
    </td></tr>
  </table>

  <input style="width:unset" id="Schicht-Automatic-Bis" name="Schicht-Automatic-Bis"
         type="checkbox" onclick="setEndDate()"
         <?= $AutomaticBis ? 'checked' : '' ?>>
  Endzeit von Dauer<br>

  <input style="width:unset" id="Schicht-Anschlussschicht" name="Schicht-Anschlussschicht"
         type="checkbox" <?= $Anschlussschicht ? 'checked' : '' ?>>
  Anschlussschicht vorbereiten<br>

  <p>
    <button name="NewSchicht" value="1">Schicht anlegen</button>
    <button name="ChangeSchicht" value="1">Ändern</button>
    <button name="DeleteSchicht" value="1">Löschen</button>
  </p>
</form>
<?php endif; ?>


<!-- ======================================================
     ABSCHNITT 3: Übersichtstabelle aller Dienste/Schichten
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
