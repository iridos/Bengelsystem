<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$pagename = "Admin Dienste/Schichten (Formulare)";
$db_link = ConnectDB();
require '_login.php';
require_once '_functions.php';

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
if (isset($_POST['ChangeDienst'])) {
    $ok = ChangeDienst(
        $db_link, $DienstID,
        $_POST['Dienst-Was'], $_POST['Dienst-Wo'], $_POST['Dienst-Info'],
        $_POST['Dienst-Leiter'], $_POST['Dienst-Gruppe'], $_POST['HelferLevel']
    );
    if ($ok === false) {
        $message .= "Dienst konnte nicht geändert werden (Elterndienst darf nicht der Dienst selbst oder einer seiner Unterdienste sein).<br>";
    } else {
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }
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
        $_POST['Schicht-Soll'],
        DauerAusFormular($_POST['Schicht-Dauer-H'] ?? 0, $_POST['Schicht-Dauer-M'] ?? 0)
    );
    header("Location: " . $_SERVER['PHP_SELF'] . "#schicht"); exit;
}

// Checkbox-Stellung "Endzeit von Dauer" / "Anschlussschicht vorbereiten":
// Kommt das Schicht-Formular selbst (erkennbar an Schicht-Von), ist der aktuelle
// POST-Wert maßgeblich und wird gemerkt. Sonst (v.a. nach dem Redirect unten,
// wo $_POST leer ist) wird die zuletzt gemerkte Stellung übernommen -- vorher
// gingen beide Flags bei jedem Redirect (z.B. direkt nach "Schicht anlegen")
// verloren.
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
    $Dauer = DauerAusFormular($_POST['Schicht-Dauer-H'] ?? 0, $_POST['Schicht-Dauer-M'] ?? 0);
    if ($AutomaticBis) {
        $Bis = DauerEndzeit($Von, $Dauer);
    }
    NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll, $Dauer, $HelferName);
    $_SESSION['SchichtID'] = LastInsertId($db_link);
    // Anschlussschicht: die nächste Schicht soll direkt an diese anschließen
    // (Von = Bis der gerade angelegten Schicht). Muss hier vor dem Redirect
    // gemerkt werden -- ein Check erst im Schicht-Auswahl-Loop weiter unten
    // käme zu spät, weil dieser Zweig hier immer per exit endet, bevor die
    // Anzeige erreicht wird. DateTime normalisiert das Format unabhängig davon,
    // ob $Bis gerade per AutomaticBis berechnet oder roh aus dem
    // datetime-local-Feld kam.
    $_SESSION['Schicht-AnschlussVon'] = $Anschlussschicht ? (new DateTime($Bis))->format('Y-m-d H:i:s') : null;
    header("Location: " . $_SERVER['PHP_SELF'] . "#schicht"); exit;
}

if (isset($_POST['DeleteSchicht'])) {
    if (!DeleteSchicht($db_link, $SchichtID, false)) {
        $message .= "Erst Schicht leeren<br>";
    } else {
        $SchichtID = 0;
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

// GET: Dienst oder Schicht direkt aufrufen (z. B. per Link aus AdminDienste.php)
if (isset($_GET['DienstID']))  { $DienstID  = (int)$_GET['DienstID'];  }
if (isset($_GET['SchichtID'])) { $SchichtID = (int)$_GET['SchichtID']; }

// Session persistieren
$_SESSION['DienstID']  = $DienstID;
$_SESSION['SchichtID'] = $SchichtID;

// Zurück-Ziel: gemerkte Übersichts-URL, der zuletzt gezeigte Dienst wird dort aufgeklappt
// (Die URL stammt aus der Session, die AdminDienste.php selbst gesetzt hat.)
$zurueckZiel = UrlMitParametern($_SESSION['AdminDiensteUrl'] ?? 'AdminDienste.php',
                                $DienstID ? ['aktiv' => (int)$DienstID] : [], ['loeschen']);

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

<a href="<?= htmlspecialchars($zurueckZiel) ?>"><button type="button" class="back"><b>&larrhk;</b></button></a>
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
<?php AusgabeDienstDatalist($db_link, 'dienst-search-datalist'); ?>
    </th></tr>
  </table>

  <p><noscript><button name="ShowSchichten" value="1">Dienst anzeigen</button></noscript></p>

<?php if ($DienstID): ?>
  <table border="0" class="commontable">
    <tr><td>Was</td></tr>
    <tr><td><input name="Dienst-Was" type="text" value="<?= htmlspecialchars($Was ?? '') ?>"></td></tr>
    <tr><td>Wo</td></tr>
    <tr><td><input name="Dienst-Wo" type="text" value="<?= htmlspecialchars($Wo ?? '') ?>"></td></tr>
    <tr><td>Info</td></tr>
    <tr><td>
    <textarea name="Dienst-Info" rows="3"><?= htmlspecialchars($Info ?? '') ?></textarea>
    </td></tr>
    <tr><td>Leiter</td></tr>
    <tr><td>
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
    <tr><td>Gruppe</td></tr>
    <tr><td>
      <input type="text" name="Dienst-Gruppe" list="dienste-datalist"
             placeholder="ID oder Name eingeben, leer = Top-Level"
             value="<?= $Gruppe ? (int)$Gruppe . ' - ' . htmlspecialchars($AlleDienstNamen[$Gruppe] ?? '?') : '' ?>">
<?php AusgabeDienstDatalist($db_link, 'dienste-datalist', true, $DienstID); ?>
    </td></tr>
    <tr><td>HelferLevel</td></tr>
    <tr><td>
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
  </table>
  <p>
    <button name="NewDienst" value="1">Dienst anlegen</button>
    <button name="ChangeDienst" value="1">Ändern</button>
    <button name="DeleteDienst" value="1">Löschen</button>
  </p>
<?php endif; ?>
</form>

<!-- ======================================================
     ABSCHNITT 2: Schicht anlegen / bearbeiten
     ====================================================== -->
<?php if ($DienstID): ?>
<h3 id="schicht">Schicht bearbeiten</h3>
<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>#schicht">
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
       . htmlspecialchars($zeile['TagVon']) . "</option>";
}
mysqli_free_result($db_erg);

// Anschlussschicht: direkt nach "Schicht anlegen" gesetzt (siehe NewSchicht-
// Verarbeitung oben). Einmalig anwenden und aus der Session löschen, damit es
// nicht bei der nächsten, unabhängigen Navigation (z.B. andere Schicht manuell
// ausgewählt) nochmal zuschlägt.
if (!empty($_SESSION['Schicht-AnschlussVon'])) {
    $Von = $_SESSION['Schicht-AnschlussVon'];
    if ($AutomaticBis && $Dauer) {
        $Bis = DauerEndzeit($Von, $Dauer);
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
             onKeyUp="setEndDate()" onChange="setEndDate()" value="<?= htmlspecialchars($Von ?? '') ?>" required>
    </td></tr>
    <tr><td>Dauer</td></tr>
    <tr><td>
<?php [$DauerH, $DauerM] = DauerZerlegen($Dauer); ?>
      <input id="Schicht-Dauer-H" name="Schicht-Dauer-H" type="number" min="0" max="99" style="width:3em"
             onChange="setEndDate()" onKeyUp="setEndDate()" value="<?= (int)$DauerH ?>" required> h
      <input id="Schicht-Dauer-M" name="Schicht-Dauer-M" type="number" min="0" max="59" style="width:3em"
             onChange="setEndDate()" onKeyUp="setEndDate()" value="<?= (int)$DauerM ?>" required> m
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

<hr>

<a href="<?= htmlspecialchars($zurueckZiel) ?>"><button type="button" class="back"><b>&larrhk;</b></button></a>
</div>
</body>
</html>
