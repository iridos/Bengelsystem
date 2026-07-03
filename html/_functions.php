<?php

require_once 'konfiguration.php';

function PageHeader ($pagename, $eventname = EVENTNAME, $jquery = JQUERY){
    $header = <<<HEADER
    <!doctype html>
    <html>
    <head>
      <title>$pagename $eventname </title>
      <link rel="stylesheet" href="css/style_common.css"/>
      <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
      <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
      <meta name="viewport" content="width=480" />
      <script src="$jquery" type="text/javascript"></script>
      <script src="js/helferdb.js" type="text/javascript"></script>
      <script> collapse_table_rows();</script>
    </head>
    <body onload="setEndDate();">

HEADER; //<?vim this bracket is just here for vim syntax highlighting
    return $header;
}

function TableHeader ($pagename, $backlink, $eventname = EVENTNAME, $backlinkTop=""){
    // pagename: name of the page for display in title
    // backlink: to what page does the "back" link at the top of the page point
    // eventname: fill from constant EVENTNAME. Change needed only for special cases;
    // backlinkTop: if there are several levels to go back until the top, need to 
    //     present a way to jump to the top. Maybe better as an array and then
    //     presenting a path-like structure to allow jumping to any level above?
    $tablehead = <<<TABLEHEAD
    <div style="width: 100%;">
    <table class="commontable">
        <tr>
        <th>
        <a href='$backlink'> $backlinkTop
        <button name="BackHelferdaten">
        <b>&larrhk;</b>
        </button> &nbsp;
        </a>
       <b>$pagename $eventname</b>
       </th>
       </tr>
    </table>
TABLEHEAD; // <?vim
    return $tablehead;
}
// Aus  *AlleSchichten.php
function SchichtInfo($SchichtID, &$Was, &$Wo, &$Dauer, &$Leiter, &$LeiterHandy, &$LeiterEmail, &$Info)
    {
    $db_link = ConnectDB();
    $zeile = DetailSchicht($db_link, $SchichtID);
    if(!isset($zeile['Was'])){
    //error_log("Zeile not set in Schichtinfo");
    //error_log("called with: SchichtID $SchichtID $Was, $Wo, $Dauer, $Leiter, $LeiterHandy etc");
    // Das ist vermutlich kein Fehler mehr, wenn wir den selben Account mehrfach auf die selbe Schicht lassen für Familien etc
    }
    $Was = $zeile['Was'];
    $Wo = $zeile['Wo'];
    $Dauer = $zeile['Dauer'];
    $Leiter = $zeile['Name'];
    $LeiterHandy =  $zeile['Handy'];
    $LeiterEmail =  $zeile['Email'];
    $Info = $zeile['Info'];
    $db_link->close();
    return;
}

function HelferAuswahlButton($db_link, $AliasHelferID)
{
    echo '<b>Helfer w&auml;hlen:<b>';
    echo '  <form style="display:inline-block;" method=post>';
    echo '  <select style="height:33px;width:350px;" name="AliasHelferID" id="AliasHelferID" onchange="submit()">';
    $db_erg = HelferListe($db_link);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        if ($AliasHelferID != $zeile['HelferID']) {
            echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</optionen>";
        } else {
            echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</optionen>";
            $selectedSet = true;
        }
    }
    if( ! isset($selectedSet) or ! $selectedSet ) {
      echo "<option value='none' selected='selected'>Bitte auswählen</optionen>";
    }

    echo '</select></form>';
}

function HelferLevelAnzeigeCheckPOST($db_link,$ZielHelferID,$AdminStatus,$AdminID){
    // POST nach GET, denn wir behalten gets
    if (isset($_GET['helfer-level-anzeige'])) {
        $_SESSION["HelferLevelAnzeige"] = $_GET['helfer-level-anzeige'];
    }
    // jeder soll sich alle HelferLevel anzeigen lassen koennen
    if (isset($_POST['helfer-level-anzeige'])) {
        $_SESSION["HelferLevelAnzeige"] = $_POST['helfer-level-anzeige'];
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

function AlleSchichtenCheckPOST($db_link,$ZielHelferID,$AdminStatus,$AdminID) {

// Wenn es ein Admin ist ZielHelferID AliasHelferID, sonst HelferID
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Neu Schicht fuer Helfer Eintragen
        ///////////////////////////////////////////////////////////
            $messages = [];
        if (isset($_POST['plusschicht'])) {
            // Nutzer hat hier zuletzt etwas geändert und wir klappen das deshalb auf,
            // indem wir unten target=active setzen
            $_SESSION["SchichtIdAktiv"] = $SchichtID = $_POST['plusschicht'];
            if (empty($messages)) {
                // Helfer Schicht zuweisen
                // wenn es ein Admin ist, die AdminID übergeben, ansonsten 0
                // TODO: immer AdminID angeben, die Funktionen in SQL testen, ob ZielHelferID==AdminID
                $db_erg = HelferSchichtZuweisen($db_link, $ZielHelferID, $SchichtID, $AdminStatus == 1 ? $AdminID : 0);

                $HelferName = '';
                $HelferEmail = '';
                $HelferHandy = '';
            } else {
                // Fehlermeldungen ausgeben:
                echo '<div class="error"><ul>';
                foreach ($messages as $message) {
                    echo '<li>' . htmlspecialchars($message) . '</li>';
                }
            echo '</ul></div>';
            exit;
            }
        }
        if (isset($_POST['minusschicht'])) {
            // Mich aus Schicht entfernen
                $messages = [];
                // Nutzer hat hier zuletzt etwas geaendert und wir klappen das deshalb auf:
                $_SESSION["SchichtIdAktiv"] = $SchichtID = $_POST['minusschicht'];

            if (empty($messages)) {
                // Helfer aus Schicht entfernen
                $db_erg = HelferVonSchichtLoeschen_SchichtID($db_link, $ZielHelferID, $SchichtID, $AdminStatus == 1 ? $AdminID : 0);
            } else {
                // Fehlermeldungen ausgeben:
                echo '<div class="error"><ul>';
                foreach ($messages as $message) {
                        echo '<li>' . htmlspecialchars($message) . '</li>';
                }
                echo '</ul></div>';
                exit;
            }
        }
    HelferLevelAnzeigeCheckPOST($db_link,$ZielHelferID,$AdminStatus,$AdminID);
    // Wenn es ein Admin wird ZielHelferID AliasHelferID, sonst HelferID
        if ($AdminStatus == 1 && isset($_POST['AliasHelferID'])){
            $_SESSION["AliasHelferID"] = $AliasHelferID = $_POST['AliasHelferID'];
            $db_erg = Helferdaten($db_link, $AliasHelferID);
                while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
                    $AliasHelferName = $zeile['Name'];
                }
           $_SESSION["AliasHelferName"] = $AliasHelferName;
        }
        //header("Location: " . $_SERVER['PHP_SELF']);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

function HelferLevelAuswahl($db_link,$HelferLevelAnzeige){
    echo '<select style="width:200px;margin:6px 10px;" name="helfer-level-anzeige" onchange="submit()">';
    $alleHelferLevel = alleHelferLevel($db_link);
    foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
        $selected = ($HelferLevelIteration == $HelferLevelAnzeige) ? "selected" : "" ;
        echo "<option value='$HelferLevelIteration' $selected>$HelferLevelBeschreibung</option>";
    }
    echo '</select>';
    return;
}
function ZeigeHelferLevelTabelle($db_link,$HelferLevel,$HelferLevelAnzeige){
    // fuer Anzahlanzeige in Ueberschrift
    $iAlleSchichtenCount = AlleSchichtenCount($db_link);
    $Belegung = AlleBelegteSchichtenCountMitSurplus($db_link);
    $iBelegteSchichtenCount = $Belegung['besetzt'];
    $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
    // "Alle Schichten der Con" (Gesamtstatistik besetzt/gewollt)
    echo '<table  class="commontable">';
    echo "<tr class='infoheader'>";
    echo "<th colspan='5'>Alles: ";
    echo "Besetzt (+Überbelegt) / Gesamt&nbsp;&nbsp;&nbsp; ";
    echo "${iBelegteSchichtenCount}(+${iueberBelegteSchichtenCount})/$iAlleSchichtenCount </th></tr>";

    $alleHelferLevel = alleHelferLevel($db_link);
    // Summe Ausgabe alle Dienste pro Helferlevel
    foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
        $meine = "";
        if ($HelferLevelIteration == $HelferLevel) {
            $meine = "<div style='float:right'>&leftarrow; Schichten für mich zum eintragen</div>";
        } else { $meine = "<div style='float:right'>Eintragen hier nur nach Rücksprache mit Orga</div>";}
        if ($HelferLevelIteration == $HelferLevelAnzeige) {
            $meine = "$meine  Schichten werden gerade unten angezeigt";
        }
        $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelIteration);
        $Belegung = AlleBelegteSchichtenCountMitSurplus($db_link,$HelferLevelIteration);
        $iBelegteSchichtenCount = $Belegung['besetzt'];
        $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
        echo "<tr class='infoheader'><th colspan='5' >&nbsp;&nbsp; &rightarrow; Schichten  $HelferLevelBeschreibung  ";
        echo "${iBelegteSchichtenCount}(+$iueberBelegteSchichtenCount)/$iAlleSchichtenCount  $meine</th></tr>";
    }
    echo '</table>';
}

// ============================================================================
// ZeigeDiensteUndSchichten — gemeinsame Renderfunktion
//
// $opts-Schlüssel (alle optional):
//
//   'modus'               string  'SchichtEintragen' — +/- Buttons (Default)
//                                 'admin_edit'       — Bearbeiten/Löschen-Buttons
//   'zeitbereich'         bool    Zeitbereich-Auswahl anzeigen (Default: true)
//   'helferlevel_auswahl' bool    HelferLevel-Dropdown anzeigen (Default: true)
//   'helferlevel_tabelle' bool    HelferLevel-Statistik-Tabelle anzeigen (Default: true)
//   'meine_schichten_link' string URL für den "Dienstplan"-Link (Default: 'MeineSchichten.php')
//   'zeigeHelferAuswahl'  bool    Admin-Helferauswahl-Button anzeigen (Default: false)
//   'AliasHelferID'       int     Welcher Helfer bearbeitet wird (Admin-Modus, Default: $HelferID)
//   'zeigeHierarchie'     bool    ElternDienstID-Einrückung anzeigen (Default: false)
//   'suchfilter'          string  Vorausgefüllter Suchwert — normalerweise leer lassen,
//                                 die Funktion liest $_GET['suche'] selbst (Default: '')
//   'HelferLevelAnzeige'  int     Angezeigtes HelferLevel (Default: aus Session)
//   'HelferLevel'         int     HelferLevel des eingeloggten Helfers (Default: aus Session)
//   'AdminStatus'         int     (Default: aus Session)
//   'AdminID'             int     (Default: aus Session)
// ============================================================================

function ZeigeDiensteUndSchichten($db_link, $HelferID, array $opts = []): void
{
    // Variablen aus _zeitbereich.php / konfiguration.php (werden im globalen Scope gesetzt)
    global $start_date, $ZeitBereich, $TageNamenDeutsch;

    // --- Defaults -----------------------------------------------------------
    $o = array_merge([
        'modus'                => 'SchichtEintragen',
        'zeitbereich'          => true,
        'helferlevel_auswahl'  => true,
        'helferlevel_tabelle'  => true,
        'meine_schichten_link' => 'MeineSchichten.php',
        'zeigeHelferAuswahl'   => false,
        'AliasHelferID'        => $HelferID,
        'zeigeHierarchie'      => false,
        'suchfilter'           => '',
        'HelferLevelAnzeige'   => $_SESSION['HelferLevelAnzeige'] ?? ($_SESSION['HelferLevel'] ?? 1),
        'HelferLevel'          => $_SESSION['HelferLevel'] ?? 1,
        'AdminStatus'          => $_SESSION['AdminStatus'] ?? 0,
        'AdminID'              => $_SESSION['AdminID'] ?? 0,
    ], $opts);

    $HelferLevelAnzeige = $o['HelferLevelAnzeige'];
    $HelferLevel        = $o['HelferLevel'];

    // --- Optionaler Admin-Helfer-Auswahl-Button -----------------------------
    if ($o['zeigeHelferAuswahl']) {
        HelferAuswahlButton($db_link, $o['AliasHelferID']);
    }

    // --- Suchfilter: GET-Formular außerhalb des POST-Formulars --------------
    // Muss VOR dem <form method="post"> stehen, damit kein form-in-form entsteht.
    $suchfilter = trim($o['suchfilter']) !== ''
        ? trim($o['suchfilter'])
        : trim($_GET['suche'] ?? '');

    echo '<form method="get" action="#suche" class="suchfilter-form">';
    echo '<input type="text" name="suche" placeholder="Dienst suchen…" '
       . 'value="' . htmlspecialchars($suchfilter) . '">';
    echo '&nbsp;<button type="submit">&#128269;</button>';
    if ($suchfilter !== '') {
        echo '&nbsp;<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">'
           . '<button type="button">&#x2715; Filter</button></a>';
    }
    echo '</form>';

    if ($suchfilter !== '') {
        _ZeigeSuchfilterHinweis($suchfilter);
    }

    // --- Dienstplan-Zusammenfassung -----------------------------------------
    echo '<form method="post" action="#action">';

    $db_erg     = SchichtenSummeEinesHelfers($db_link, $HelferID);
    $zeile      = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);
    $helferName = htmlspecialchars($_SESSION['AliasHelferName'] ?? $_SESSION['HelferName'] ?? '');
    $zielLink   = htmlspecialchars($o['meine_schichten_link']);

    echo '<table class="commontable"><tr class="header">';
    echo "<th onclick=\"window.location.href='{$zielLink}'\">";
    echo '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">';
    echo "Dienstplan von {$helferName} (";
    echo (int)$zeile['Anzahl'];
    echo ' Schichten, ';
    echo $zeile['Dauer'] / 3600;
    echo ' Stunden)';
    echo '</th></tr></table>';

    // --- Zeitbereich + Buttons + HelferLevel-Dropdown in einer Zeile --------
    echo '<table class="commontable">';
    if ($o['zeitbereich']) {
        require_once '_zeitbereich.php';
        $Bereich = AusgabeZeitbereichZeile($start_date, $ZeitBereich, $TageNamenDeutsch, $_SERVER['PHP_SELF']);
        $MeinVon = $Bereich['MeinVon'];
        $MeinBis = $Bereich['MeinBis'];
    } else {
        $MeinVon = null;
        $MeinBis = null;
    }

    echo "<button type='button' onclick='expand_all_table_rows();'>Alles Ausklappen</button>";

    if ($o['helferlevel_auswahl']) {
        HelferLevelAuswahl($db_link, $HelferLevelAnzeige);
    }
    // _zeitbereich.php öffnet die Tabelle, schliesst sie nicht — wir schliessen hier
    echo '</table>';

    // --- HelferLevel-Statistik-Tabelle --------------------------------------
    if ($o['helferlevel_tabelle']) {
        ZeigeHelferLevelTabelle($db_link, $HelferLevel, $HelferLevelAnzeige);
    }

    // --- DB-Abfrage ---------------------------------------------------------
    $db_erg = AlleSchichtenImZeitbereich($db_link, $MeinVon, $MeinBis, $HelferLevelAnzeige);

    // --- Hierarchie-Index aufbauen (optional) -------------------------------
    $hierarchieIndex = [];
    if ($o['zeigeHierarchie']) {
        $hierarchieIndex = _BaueHierarchieIndex($db_link);
    }

    // --- Meine belegten Schichten für Grün-Markierung -----------------------
    $MeineDienste = SchichtIdArrayEinesHelfers($db_link, $HelferID);

    // --- Haupttabelle -------------------------------------------------------
    echo '<table class="commontable collapsible">';

    $OldWas = '';
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $Was = $zeile['Was'];

        // Suchfilter: Zeilen überspringen, die nicht passen
        if ($suchfilter !== '' && stripos($Was, $suchfilter) === false) {
            continue;
        }

        // --- Dienst-Header --------------------------------------------------
        if ($Was !== $OldWas) {
            $SchichtID = $zeile['SchichtID'];
            $DienstID  = $zeile['DienstID'];

            $iAlleSchichtenCount         = AlleSchichtenCount($db_link, $HelferLevelAnzeige, $DienstID);
            $Belegung                    = AlleBelegteSchichtenCountMitSurplus($db_link, $HelferLevelAnzeige, $DienstID);
            $iBelegteSchichtenCount      = $Belegung['besetzt'];
            $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
            $ueberBelegteSchichten       = ($iueberBelegteSchichtenCount > 0)
                ? "[+{$iueberBelegteSchichtenCount}]" : '';

            $indent = '';
            if ($o['zeigeHierarchie'] && isset($hierarchieIndex[$DienstID])) {
                //$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $hierarchieIndex[$DienstID]['tiefe']);
                $tiefe=$hierarchieIndex[$DienstID]['tiefe'];
                $indent = "ID:$DienstID [$tiefe]  ";
            }

            echo "<tr class='header'><th colspan='5' style='width:100%'><span>+</span> ";
            echo $indent . htmlspecialchars($Was);
            echo " ({$iBelegteSchichtenCount}/{$iAlleSchichtenCount}) {$ueberBelegteSchichten}";
            echo " <!-- Abfrage {$HelferLevel}, {$DienstID} -->";
            echo "</th></tr>";

            SchichtInfo($SchichtID, $InfoWas, $InfoWo, $InfoDauer, $Leiter, $LeiterHandy, $LeiterEmail, $Info);
            echo "<tr><td colspan=5 style='background:lightblue'>";
            echo '<b>Beschreibung:</b> ' . htmlspecialchars($Info ?? '') . '<br><br>';
            echo '<b>Ort:</b> ' . htmlspecialchars($InfoWo ?? '') . '<br>';
            echo '<b>Ansprechpartner:</b> ' . htmlspecialchars($Leiter ?? '');
            if (!empty($LeiterHandy)) { echo ', ' . htmlspecialchars($LeiterHandy); }
            if (!empty($LeiterEmail)) { echo ', ' . htmlspecialchars($LeiterEmail); }

            if ($o['modus'] === 'admin_edit') {
                echo '&nbsp;&nbsp;<a href="AdminDienste.php?DienstID=' . (int)$DienstID . '">'
                   . '<button type="button" style="width:200px">Dienst bearbeiten</button></a>';
            }

            echo "</td></tr>\n";
            $OldWas = $Was;
        }

        // --- Farbe nach Belegungsgrad ---------------------------------------
        $Color = 'red';
        if ($zeile['Ist'] > 0)               { $Color = 'yellow'; }
        if ($zeile['Ist'] >= $zeile['Soll']) { $Color = 'green';  }

        // --- Zeitformatierung -----------------------------------------------
        $Von = $zeile['Ab'];
        $Bis = $zeile['Bis'];
        if (substr($Von, 0, 2) === substr($Bis, 0, 2)) { $Bis = substr($Bis, 2); }
        $Von = substr($Von, 2);

        // --- Zeilen-Stil (eigene Schicht = grün) ----------------------------
        $SchichtID = $zeile['SchichtID'];
        if (in_array($SchichtID, $MeineDienste)) {
            $rowstyle = ' style="background-color:lightgreen" ';
            $regtext  = '<br><center>Meine!</center>';
        } else {
            $rowstyle = 'dbinfo="SchichtID:' . (int)$SchichtID . ';helferlvl:' . (int)$HelferLevel . '" ';
            $regtext  = '';
        }

        // Aktive Schicht nicht einklappen
        if (isset($_SESSION['SchichtIdAktiv']) && $_SESSION['SchichtIdAktiv'] == $SchichtID) {
            $rowstyle .= " target='active' ";
        }

        // --- Tabellenzeile --------------------------------------------------
        echo '<tr ' . $rowstyle
           . 'onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID='
           . (int)$SchichtID . '#Info\';">';
        echo '<td>' . htmlspecialchars($zeile['Tag']) . '</td>';
        echo '<td>' . htmlspecialchars($Von) . '</td>';
        echo '<td>' . htmlspecialchars($Bis) . '</td>';
        echo "<td bgcolor='{$Color}'>" . (int)$zeile['Ist'] . '/' . (int)$zeile['Soll'] . '</td>';

        echo "<td style='width:10%;white-space:nowrap'>";
        if ($o['modus'] === 'SchichtEintragen') {
            echo "<button name='plusschicht' value='" . (int)$SchichtID . "'>+</button>";
            echo "&nbsp;&nbsp;<button name='minusschicht' value='" . (int)$SchichtID . "'>&ndash;</button>";
            echo $regtext;
        } elseif ($o['modus'] === 'admin_edit') {
            echo '<a href="AdminDienste.php?SchichtID=' . (int)$SchichtID . '">'
               . '<button type="button">&#9998;</button></a>';
            echo '&nbsp;<button type="button" '
               . 'onclick="if(confirm(\'Schicht löschen?\')){'
               . 'document.getElementById(\'deleteSchichtID\').value=' . (int)$SchichtID . ';'
               . 'document.getElementById(\'deleteSchichtForm\').submit();}">&#128465;</button>';
        }
        echo '</td>';
        echo "</tr>\n";
    }

    echo '</table>';
    mysqli_free_result($db_erg);
    echo '</form>';

    // Verstecktes Formular für Schicht-Löschen im Admin-Edit-Modus
    if ($o['modus'] === 'admin_edit') {
        echo '<form id="deleteSchichtForm" method="post" action="AdminDienste.php">';
        echo '<input type="hidden" id="deleteSchichtID" name="DeleteSchichtID" value="">';
        echo '</form>';
    }
}

// --- Hilfsfunktionen (nur intern genutzt) -----------------------------------

/**
 * Baut einen Index aller Dienste mit ihrer Hierarchie-Tiefe auf.
 * Gibt ein Array zurück: [ DienstID => ['tiefe' => int, 'eltern' => int|null] ]
 */
function _BaueHierarchieIndex($db_link): array
{
    $result   = GetDienste($db_link);
    $rohdaten = [];
    while ($zeile = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $rohdaten[$zeile['DienstID']] = [
            'eltern' => $zeile['ElternDienstID'],
            'tiefe'  => 0,
        ];
    }
    // Tiefe iterativ berechnen — max. 10 Ebenen schützt vor Zyklen
    for ($pass = 0; $pass < 10; $pass++) {
        foreach ($rohdaten as $id => &$eintrag) {
            $eltern = $eintrag['eltern'];
            if ($eltern !== null && isset($rohdaten[$eltern])) {
                $eintrag['tiefe'] = $rohdaten[$eltern]['tiefe'] + 1;
            }
        }
        unset($eintrag);
    }
    return $rohdaten;
}

function _ZeigeSuchfilterHinweis(string $suchfilter): void
{
    echo '<div style="background:#fff3cd;padding:4px 8px;margin:4px 0;">';
    echo '&#128269; Filter aktiv: <b>' . htmlspecialchars($suchfilter) . '</b>';
    echo '&nbsp;&nbsp;<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">Filter entfernen</a>';
    echo '</div>';
}

