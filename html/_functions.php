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

function TableHeader ($pagename, $backlink, $eventname = EVENTNAME, $backlinkTop="", $upLinkHtml = ""){
    // pagename: name of the page for display in title
    // backlink: to what page does the "back" link at the top of the page point
    // eventname: fill from constant EVENTNAME. Change needed only for special cases;
    // backlinkTop: if there are several levels to go back until the top, need to 
    //     present a way to jump to the top. Maybe better as an array and then
    //     presenting a path-like structure to allow jumping to any level above?
    // upLinkHtml: fertiges HTML (Button/Link) für "eine Ebene höher", z.B. aus
    //     DienstEbeneUpLinkHtml(). Leer = kein Up-Button (Default, abwärtskompatibel).
    //     Steht bewusst außerhalb des <a href="$backlink">-Wrappers, damit es sein
    //     eigenes Ziel hat statt versehentlich mitverlinkt zu werden.
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
        $upLinkHtml
       <b>$pagename $eventname</b>
       </th>
       </tr>
    </table>
TABLEHEAD; // <?vim
    return $tablehead;
}

/**
 * Liefert fertiges HTML für den "Eine Ebene höher"-Button in der Dienst-Baum-
 * Navigation, oder "" wenn man auf Top-Level ist (Button dann einfach weglassen).
 * Ziel wird über ElternDienstID des aktuellen Dienstes bestimmt:
 * - Elterndienst vorhanden  -> ?dienst=<ElternDienstID>
 * - kein Elterndienst (Kind von Top-Level) -> Seite ohne dienst-Parameter, also Top-Level
 * Gedacht zum Einsatz neben dem bestehenden Zurück-Button (TableHeader $upLinkHtml),
 * NICHT als Ersatz für den Zurück-Button -- der bleibt der Ausstieg aus der ganzen
 * Baum-Ansicht, dieser hier bleibt innerhalb der Hierarchie.
 */
function DienstEbeneUpLinkHtml($db_link, ?int $AktuellerDienst): string
{
    if ($AktuellerDienst === null) {
        return ''; // Top-Level: kein Elternlevel, kein Button
    }
    $dienst = GetEinzelDienst($db_link, $AktuellerDienst);
    $ElternDienstID = $dienst['ElternDienstID'] ?? null;

    $ziel = htmlspecialchars($_SERVER['PHP_SELF']);
    if ($ElternDienstID) {
        $ziel .= '?dienst=' . (int)$ElternDienstID;
    }
    // sonst: Elterndienst ist Top-Level -> Seite ohne dienst-Parameter

    return '<button type="button" name="EbeneHoch" title="Eine Ebene höher" '
         . 'onclick="window.location.href=\'' . $ziel . '\';">'
         . '<b>&uarr;</b>'
         . '</button> &nbsp;';
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

/**
 * Rendert eine einzelne HelferLevel-Zeile mit Besetzt/Gesamt-Zahlen.
 * $DienstID=-1 (Default) = ganze Con, sonst nur dieser Dienst
 * (bzw. sein Teilbaum, wenn $Rekursiv=true) -- für einzelne Dienst-Ebenen.
 */
function _ZeigeHelferLevelZeile($db_link, $HelferLevelIteration, $HelferLevelBeschreibung,
                                 $HelferLevel, $HelferLevelAnzeige, $DienstID = -1, $Rekursiv = false): void
{
    if ($HelferLevelIteration == $HelferLevel) {
        $meine = "<div style='float:right'>&leftarrow; Schichten für mich zum eintragen</div>";
    } else {
        $meine = "<div style='float:right'>Eintragen hier nur nach Rücksprache mit Orga</div>";
    }
    if ($HelferLevelIteration == $HelferLevelAnzeige) {
        $meine .= "  Schichten werden gerade unten angezeigt";
    }

    $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelIteration, $DienstID, $Rekursiv);
    $Belegung            = AlleBelegteSchichtenCountMitSurplus($db_link, $HelferLevelIteration, $DienstID, $Rekursiv);
    $iBelegteSchichtenCount      = $Belegung['besetzt'];
    $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];

    echo "<tr class='infoheader'><th colspan='5' >&nbsp;&nbsp; &rightarrow; Schichten  $HelferLevelBeschreibung  ";
    echo "${iBelegteSchichtenCount}(+$iueberBelegteSchichtenCount)/$iAlleSchichtenCount  $meine</th></tr>";
}

function ZeigeHelferLevelTabelle($db_link, $HelferLevel, $HelferLevelAnzeige, $DienstID = -1, $Rekursiv = false)
{
    $iAlleSchichtenCount = AlleSchichtenCount($db_link, -1, $DienstID, $Rekursiv);
    $Belegung            = AlleBelegteSchichtenCountMitSurplus($db_link, -1, $DienstID, $Rekursiv);
    $iBelegteSchichtenCount      = $Belegung['besetzt'];
    $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];

    echo '<table  class="commontable">';
    echo "<tr class='infoheader'><th colspan='5'>Alles: ";
    echo "Besetzt (+Überbelegt) / Gesamt&nbsp;&nbsp;&nbsp; ";
    echo "${iBelegteSchichtenCount}(+${iueberBelegteSchichtenCount})/$iAlleSchichtenCount </th></tr>";

    $alleHelferLevel = alleHelferLevel($db_link);
    foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
        _ZeigeHelferLevelZeile($db_link, $HelferLevelIteration, $HelferLevelBeschreibung,
                                $HelferLevel, $HelferLevelAnzeige, $DienstID, $Rekursiv);
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
//   'aktiverDienst'       int     (admin_edit) Dienst, der aufgeklappt und angesprungen wird (Default: null)
//   'loeschenAktiv'       bool    (admin_edit) Mülleimer des aktiven Dienstes freigeschaltet (Default: false)
//   'alleDienste'         bool    (admin_edit) auch Dienste ohne Schicht im Zeitbereich zeigen, in Baum-Reihenfolge
//                                 (Eltern vor Kindern, eingerückt) statt alphabetisch (Default: false)
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
        'aktiverDienst'        => null,
        'loeschenAktiv'        => false,
        'alleDienste'          => false,
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
    echo '<input type="text" name="suche" list="dl-suche-dienste" placeholder="Dienst suchen…" '
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
    if ($o['zeigeHierarchie'] || (($o['modus'] === 'admin_edit') && !empty($o['alleDienste']))) {
        $hierarchieIndex = _BaueHierarchieIndex($db_link);
    }

    // --- Meine belegten Schichten für Grün-Markierung -----------------------
    $MeineDienste = SchichtIdArrayEinesHelfers($db_link, $HelferID);

    // Zuletzt bearbeiteter Dienst (admin_edit): wird aufgeklappt und angesprungen.
    // Kommt von der aufrufenden Seite (dort aus der URL), nicht aus der Session.
    $aktiverDienst = ($o['modus'] === 'admin_edit') ? $o['aktiverDienst'] : null;

    // --- Haupttabelle -------------------------------------------------------
    if ($o['modus'] === 'admin_edit') {
        _ZeigeMassenEditLeiste();
    }
    echo '<table class="commontable collapsible">';

    // Alle Zeilen einlesen, damit die Suchtreffer vorab gezählt werden können
    $zeilen = mysqli_fetch_all($db_erg, MYSQLI_ASSOC);
    mysqli_free_result($db_erg);
    $alleDienste = ($o['modus'] === 'admin_edit') && !empty($o['alleDienste']);
    if ($alleDienste) {
        $zeilen = _MitLeerenDiensten($db_link, $zeilen, $HelferLevelAnzeige);
    }
    $treffer = [];
    if ($suchfilter !== '') {
        foreach ($zeilen as $z) {
            if (stripos($z['Was'], $suchfilter) !== false) { $treffer[$z['DienstID']] = true; }
        }
    }
    // Wenige Treffer: gleich aufklappen
    $suchAufklappen = (count($treffer) > 0 && count($treffer) <= SUCHE_AUTOAUFKLAPPEN);

    // Nach einem Löschen bleibt der Löschen-Haken des Dienstes gesetzt
    $loeschenAktiv = ($o['modus'] === 'admin_edit') && !empty($o['loeschenAktiv']);
    $loeschenDienst = false;

    $edit = $alleDienste ? _AdminEditDaten($db_link) : null;
    $OldGruppe = '';
    $dienstNamen = [];   // fuer die Vorschlagsliste im Suchfeld
    $letzteZeile = null; // letzte Schicht des aktuellen Dienstes (Vorgaben für die "Neue Schicht"-Zeile)
    foreach ($zeilen as $zeile) {
        $Was = $zeile['Was'];
        $dienstNamen[$Was] = true;
        if ($suchfilter !== '' && stripos($Was, $suchfilter) === false) {
            continue;
        }
        // Gruppiert wird nach Dienstname (alt) bzw. nach DienstID (alleDienste: gleichnamige Dienste bleiben getrennt)
        $gruppe = $alleDienste ? 'D' . (int)$zeile['DienstID'] : $Was;
        $leer   = !empty($zeile['leer']);   // Dienst ohne Schicht im Zeitbereich (nur alleDienste)
        if ($gruppe !== $OldGruppe) {
            if ($o['modus'] === 'admin_edit' && $letzteZeile !== null) { _ZeigeNeueSchichtZeile($letzteZeile); }
            $DienstID = $zeile['DienstID'];
            $praefix = "ID:$DienstID ";
            $tiefe = 0;
            if ($alleDienste) {
                $tiefe = (int)($hierarchieIndex[$DienstID]['tiefe'] ?? 0);   // Einrückung statt "[n]"
            } elseif ($o['zeigeHierarchie'] && isset($hierarchieIndex[$DienstID])) {
                $praefix .= "[".$hierarchieIndex[$DienstID]['tiefe']."]  ";
            }
            if ($leer) {
                $d = $edit['dienste'][(int)$DienstID] ?? [];
                $InfoWo = $d['Wo'] ?? '';
                $Info   = $d['Info'] ?? '';
                $Leiter = !empty($d['Leiter']) ? ($edit['helfer'][(int)$d['Leiter']] ?? '') : '';
                $LeiterHandy = $LeiterEmail = null;
            } else {
                SchichtInfo($zeile['SchichtID'], $InfoWas, $InfoWo, $InfoDauer, $Leiter, $LeiterHandy, $LeiterEmail, $Info);
            }
            $dienstAktiv    = ($aktiverDienst !== null && (int)$aktiverDienst === (int)$DienstID);
            $loeschenDienst = $dienstAktiv && $loeschenAktiv;
            _ZeigeDienstHeader($db_link, $DienstID, $Was, $InfoWo, $Info, $Leiter, 
                       $LeiterHandy, $LeiterEmail,$HelferLevel, $HelferLevelAnzeige, $o['modus'], $praefix,
                       $tiefe, true, $dienstAktiv, $suchAufklappen, $loeschenDienst);
            $OldGruppe = $gruppe;
        }
        if (!$leer) {
            _ZeigeSchichtZeile($zeile, $o['modus'], $MeineDienste, $HelferLevel, true, $loeschenDienst);
        }
        $letzteZeile = $zeile;
    }
    if ($o['modus'] === 'admin_edit' && $letzteZeile !== null) { _ZeigeNeueSchichtZeile($letzteZeile); }
    echo '</table>';
    echo '</form>';

    // Vorschlagsliste fuer das Suchfeld: Admin sieht alle Dienste (wie im Formular), alle anderen nur
    // die Dienste, die in ihrer Ansicht vorkommen (keine Namen von Diensten anderer HelferLevel)
    if ($o['modus'] === 'admin_edit') {
        AusgabeDienstDatalist($db_link, 'dl-suche-dienste', false);
    } else {
        echo '<datalist id="dl-suche-dienste">';
        foreach (array_keys($dienstNamen) as $name) {
            echo '<option value="' . htmlspecialchars((string)$name, ENT_QUOTES) . '">';
        }
        echo '</datalist>';
    }

    // Verstecktes Formular für Schicht-Löschen im Admin-Edit-Modus
    if ($o['modus'] === 'admin_edit') {
        echo '<form id="deleteSchichtForm" method="post" action="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">';
        echo '<input type="hidden" id="deleteSchichtID" name="DeleteSchichtID" value="">';
        echo '</form>';
        _AdminEditDatalists($db_link);
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

/**
 * Liest den aktuellen Navigations-Kontext (welcher Dienst gerade "geöffnet" ist)
 * aus dem GET-Parameter. Kein Parameter oder ungültiger Wert -> Top-Level (null).
 */
function GetAktuellenDienstKontext(): ?int
{
    if (!isset($_GET['dienst'])) {
        return null;
    }
    $id = (int)$_GET['dienst'];
    return $id > 0 ? $id : null;
}

/**
 * Rendert den Header-Block eines Dienstes (Kopfzeile + Beschreibung).
 * Die Beschreibungsfelder werden vom Aufrufer übergeben -- je nach Kontext
 * kommen sie per Schicht (SchichtInfo, alte flache Liste) oder direkt aus
 * der Dienst-Tabelle (Baum-Ansicht, auch für Dienste ohne eigene Schichten).
 */
function _ZeigeDienstHeader($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $LeiterHandy, $LeiterEmail,
                             $HelferLevel, $HelferLevelAnzeige, string $modus, string $praefix = '',
                             int $tiefe = 0, bool $foldable = true, bool $aktiv = false,
                             bool $aufklappen = false, bool $loeschen = false): void
{
    $iAlleSchichtenCount         = AlleSchichtenCount($db_link, $HelferLevelAnzeige, $DienstID);
    $Belegung                    = AlleBelegteSchichtenCountMitSurplus($db_link, $HelferLevelAnzeige, $DienstID);
    $iBelegteSchichtenCount      = $Belegung['besetzt'];
    $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
    $ueberBelegteSchichten       = ($iueberBelegteSchichtenCount > 0) ? "[+{$iueberBelegteSchichtenCount}]" : '';

    // Einrückung nach Hierarchie-Tiefe. Reines Padding fällt bei Text in der ersten Spalte kaum auf, daher
    // zusätzlich pro Ebene ein dunkler Führungsbalken im Einrückungsbereich -- als Hintergrundbild der Zelle,
    // die Tabellenstruktur (gleiche Spalten für alle Zeilen, Auf-/Zuklappen per Geschwisterzeilen) bleibt unverändert.
    $einrueckung = 20 * (int)$tiefe;
    $padding     = ($tiefe > 0 ? $einrueckung + 6 : 0) . 'px';
    $fuehrung    = ($tiefe > 0)
        ? "background-image:repeating-linear-gradient(to right,rgba(0,0,0,.35) 0,rgba(0,0,0,.35) 18px,transparent 2px,transparent 20px);"
          . "background-size:{$einrueckung}px 100%;background-repeat:no-repeat;"
        : '';
    $icon    = $foldable ? '<span>+</span> ' : '';

    // data-dienst: für JS (Löschen-Haken, Rückkehr von der Detailseite)
    // data-aktiv: gerade bearbeitet -> aufklappen + anspringen; data-aufklappen: nur aufklappen (z.B. wenige Suchtreffer)
    $headerId = " data-dienst='" . (int)$DienstID . "'" . ($aktiv ? " data-aktiv='1'" : '') . ($aufklappen ? " data-aufklappen='1'" : '');
    echo "<tr class='header'{$headerId}><th colspan='5' style='width:100%; padding-left:{$padding};{$fuehrung}'>{$icon}";
    if ($modus === 'admin_edit') {   // Markierung für den Massen-Edit (Klick soll nicht auf-/zuklappen)
        echo '<input type="checkbox" class="chk-massen" name="massen[]" value="' . (int)$DienstID . '" style="width:unset"'
           . ' title="Für Massen-Edit markieren" onclick="event.stopPropagation()"> ';
    }
    echo $praefix . htmlspecialchars($Was);
    echo " ({$iBelegteSchichtenCount}/{$iAlleSchichtenCount}) {$ueberBelegteSchichten}";
    echo " <!-- Abfrage {$HelferLevel}, {$DienstID} -->";
    echo "</th></tr>";

    $contentClass = $foldable ? "class='collapsible-content'" : '';
    $editAttr = ($modus === 'admin_edit') ? _DienstEditAttribute($db_link, $DienstID) : '';
    echo "<tr {$contentClass}><td id='DD{$DienstID}'{$editAttr} colspan=5 style='background:lightblue; padding-left:{$padding};{$fuehrung}'>";
    echo ($modus === 'admin_edit') ? '<div class="view">' : '';
    echo '<b>Beschreibung:</b> ' . htmlspecialchars($Info ?? '') . '<br><br>';
    echo '<b>Ort:</b> ' . htmlspecialchars($Wo ?? '') . '<br>';
    echo '<b>Ansprechpartner:</b> ' . htmlspecialchars($Leiter ?? '');
    if (!empty($LeiterHandy)) { echo ', ' . htmlspecialchars($LeiterHandy); }
    if (!empty($LeiterEmail)) { echo ', ' . htmlspecialchars($LeiterEmail); }
    if ($modus === 'admin_edit') {
        echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">';
        echo '<span><button type="button" title="Dienst bearbeiten" onclick="toggleEditDienst(' . (int)$DienstID . ')">&#9998;</button>'
           . '&nbsp;<a href="AdminDiensteLegacy.php?DienstID=' . (int)$DienstID . '">'
           . '<button type="button" title="Formularseite: Dienst und Schichten im Formular bearbeiten, Schichten in Serie anlegen">&#128203;</button></a></span>';
        echo '<label title="Schaltet die Mülleimer-Buttons der Schichten dieses Dienstes frei">'
           . 'Schichten löschen: <input type="checkbox" class="chk-loeschen" style="width:unset" onclick="schichtenLoeschenUmschalten(' . (int)$DienstID . ', this.checked)"'
           . ($loeschen ? ' checked' : '') . '>&nbsp;&nbsp;&nbsp;</label>';
        echo '</div>';
        echo '</div>';
    }
    echo "</td></tr>\n";
}

/**
 * Rendert eine einzelne Schicht-Zeile.
 */
function _ZeigeSchichtZeile(array $zeile, string $modus, array $MeineDienste, $HelferLevel, bool $foldable = true,
                            bool $loeschErlaubt = false): void
{
    $Color = 'red';
    if ($zeile['Ist'] > 0)               { $Color = 'yellow'; }
    if ($zeile['Ist'] >= $zeile['Soll']) { $Color = 'green';  }

    $Von = $zeile['Ab'];
    $Bis = $zeile['Bis'];
    if (substr($Von, 0, 2) === substr($Bis, 0, 2)) { $Bis = substr($Bis, 2); }
    $Von = substr($Von, 2);

    $SchichtID = $zeile['SchichtID'];
    if (in_array($SchichtID, $MeineDienste)) {
        $rowstyle = ' style="background-color:lightgreen" ';
        $regtext  = '<br><center>Meine!</center>';
    } else {
        $rowstyle = 'dbinfo="SchichtID:' . (int)$SchichtID . ';helferlvl:' . (int)$HelferLevel . '" ';
        $regtext  = '';
    }
    if (isset($_SESSION['SchichtIdAktiv']) && $_SESSION['SchichtIdAktiv'] == $SchichtID) {
        $rowstyle .= " target='active' ";
    }
    $rowClass = $foldable ? "collapsible-content" : '';
    // Inline-Editieren nur im admin_edit-Modus und wenn die Abfrage die Rohwerte geliefert hat
    $inline = ($modus === 'admin_edit') && isset($zeile['VonInput'], $zeile['BisInput']);
    $vc     = $inline ? " class='view'" : '';
    $rowId  = $inline ? " id='S" . (int)$SchichtID . "'" : '';
    if ($inline) {   // Rohwerte für den per JS gebauten Editiermodus
        [$dh, $dm] = DauerZerlegen($zeile['Dauer'] ?? null);
        $rowId .= " data-von='" . htmlspecialchars($zeile['VonInput'], ENT_QUOTES) . "'"
                . " data-bis='" . htmlspecialchars($zeile['BisInput'], ENT_QUOTES) . "'"
                . " data-dh='{$dh}' data-dm='{$dm}' data-soll='" . (int)$zeile['Soll'] . "'";
    }
    // Im Admin-Modus liegen Buttons in der Zeile: Klick auf die ganze Zeile würde dort leicht aus
    // Versehen auf die Detailseite führen, dafür gibt es dort den Auge-Button.
    $zeilenKlick = ($modus === 'admin_edit') ? ''
        : ' onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID=' . (int)$SchichtID . '#Info\';"';
    echo "<tr class='{$rowClass}'{$rowId} {$rowstyle}{$zeilenKlick}>";
    echo "<td{$vc}>" . htmlspecialchars($zeile['Tag']) . '</td>';
    // Von - Bis (Dauer) in einer Zelle über zwei Spalten
    $dauerText = isset($zeile['Dauer']) ? ' (' . DauerKurz($zeile['Dauer']) . ')' : '';
    echo "<td{$vc} colspan='2'>" . htmlspecialchars(trim($Von) . ' - ' . trim($Bis) . $dauerText) . '</td>';
    echo "<td{$vc} bgcolor='{$Color}'>" . (int)$zeile['Ist'] . '/' . (int)$zeile['Soll'] . '</td>';
    $btnClick = $inline ? " onclick='event.stopPropagation()'" : '';
    echo "<td" . ($inline ? " class='view'" : '') . " style='width:10%;white-space:nowrap'{$btnClick}>";
    if ($modus === 'SchichtEintragen') {
        echo "<button name='plusschicht' value='" . (int)$SchichtID . "'>+</button>";
        echo "&nbsp;&nbsp;<button name='minusschicht' value='" . (int)$SchichtID . "'>&ndash;</button>";
        echo $regtext;
    } elseif ($modus === 'admin_edit') {
        echo '<a class="details-link" href="DetailsSchichten.php?InfoAlleSchichtID=' . (int)$SchichtID . '#Info">'
           . '<button type="button" title="Details und eingetragene Helfer">&#128065;</button></a>&nbsp;';
        if ($inline) {
            echo '<button type="button" title="Schicht bearbeiten" onclick="toggleEditSchicht(' . (int)$SchichtID . ')">&#9998;</button>';
            // Nachfolgeschicht: direkt im Anschluss an diese (gespeicherte) Schicht, gleiche Dauer und Anzahl
            echo '&nbsp;<button type="submit" name="NachfolgeSchicht" value="' . (int)$SchichtID . '"'
               . ' title="Nachfolgeschicht anlegen (direkt im Anschluss, gleiche Dauer und Anzahl)">&#10133;</button>';
        } else {
            echo '<a href="AdminDiensteLegacy.php?SchichtID=' . (int)$SchichtID . '">'
               . '<button type="button">&#9998;</button></a>';
        }
        // Mülleimer ist ausgegraut ("gesperrt"), bis beim Dienst "Schichten löschen" angehakt wird.
        // Bewusst kein disabled-Attribut: ein deaktivierter Button löst keinen Klick aus, wir wollen
        // beim Klick auf den ausgegrauten Mülleimer aber den Haken setzen (siehe loescheSchicht() in helferdb.js).
        echo '&nbsp;<button type="button" class="btn-loeschen' . ($loeschErlaubt ? '' : ' gesperrt') . '"'
           . ($loeschErlaubt ? '' : ' style="opacity:0.4"')
           . ' title="Schicht löschen" onclick="loescheSchicht(' . (int)$SchichtID . ', this)">&#128465;</button>';
    }
    echo '</td>';
    echo '</tr>' . "\n";
}
/**
 * Zeigt eine einzelne Ebene des Dienst-Baums: Breadcrumb, Kind-Dienste zum
 * Reinklicken (mit Teilbaum-Summen), und die Schichten, die direkt am
 * aktuellen Dienst hängen (mit Eintragen-Funktion wie gewohnt).
 * Zeitraum ist noch fest (2000-2100) -- wird in einem separaten Schritt
 * durch den echten Zeitraum-Picker ersetzt.
 */
function ZeigeDienstEbene($db_link, $HelferID, array $opts = []): void
{
    $o = array_merge([
        'modus'       => 'SchichtEintragen',
        'HelferLevel' => $_SESSION['HelferLevel'] ?? 1,
        'AdminStatus' => $_SESSION['AdminStatus'] ?? 0,
        'AdminID'     => $_SESSION['AdminID'] ?? 0,
    ], $opts);

    AlleSchichtenCheckPOST($db_link, $HelferID, $o['AdminStatus'], $o['AdminID']);

    $AktuellerDienst = GetAktuellenDienstKontext();

    echo '<p><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">Top-Level</a>';
    foreach (GetDienstPfadKette($db_link, $AktuellerDienst) as $id => $was) {
        echo ' &raquo; <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?dienst=' . (int)$id . '">'
           . htmlspecialchars($was) . '</a>';
    }
    echo '</p>';

    ZeigeHelferLevelTabelle($db_link, $o['HelferLevel'], $o['HelferLevel'], $AktuellerDienst ?? -1, true);

    $Von = '2000-01-01 00:00:00';
    $Bis = '2100-01-01 00:00:00';

    $abflachen = false;
    if ($AktuellerDienst !== null) {
        $GesamtSchichtenTeilbaum = AlleSchichtenCount($db_link, -1, $AktuellerDienst, true);
        $abflachen = ($GesamtSchichtenTeilbaum <= DIENST_FLATTEN_SCHWELLWERT);
        if (isset($_GET['alle_anzeigen'])) {
            $abflachen = ($_GET['alle_anzeigen'] == '1'); // manueller Override
        }
    }
    echo '<form method="post" action="#action">';
    echo '<table class="commontable collapsible">';
    if ($abflachen) {
        _ZeigeDienstTeilbaumFlach($db_link, $AktuellerDienst, $Von, $Bis, $HelferID, $o);
    } else {
        _ZeigeDienstDrilldown($db_link, $AktuellerDienst, $Von, $Bis, $HelferID, $o);
    }
    echo '</table>';
    echo '</form>';

    if ($AktuellerDienst !== null) {
        $ziel = $abflachen ? 0 : 1;
        $text = $abflachen
            ? 'Nur direkte Schichten dieser Gruppe anzeigen'
            : 'Alle Schichten dieser Gruppe (inkl. Untergruppen) anzeigen';
        echo '<p><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?dienst=' . (int)$AktuellerDienst
           . '&alle_anzeigen=' . $ziel . '">' . $text . '</a></p>';
    }
}
/**
 * Flach-Modus: aktueller Dienst + ALLE Nachfahren, jeweils mit eigenem
 * Header + eigenen Schichten, in einer Liste -- nur für kleine Teilbäume.
 * $dienste ist bereits Eltern-vor-Kind sortiert (siehe GetDienstTeilbaum),
 * die erste Zeile ist der aktuelle Dienst selbst.
 */

function _ZeigeDienstTeilbaumFlach($db_link, $AktuellerDienst, $Von, $Bis, $HelferID, array $o): void
{
    $teilbaum = GetDienstTeilbaum($db_link, $AktuellerDienst);
    $dienste = [];
    $eigenePfadLaenge = null;
    while ($row = mysqli_fetch_assoc($teilbaum)) {
        $dienste[(int)$row['DienstID']] = $row;
        if ((int)$row['DienstID'] === (int)$AktuellerDienst) {
            $eigenePfadLaenge = substr_count($row['DienstBaumPfad'], '/');
        }
    }

    $schichten = AlleSchichtenImZeitbereich($db_link, $Von, $Bis, -1, $AktuellerDienst, true);
    $schichtenNachDienst = [];
    while ($zeile = mysqli_fetch_array($schichten, MYSQLI_ASSOC)) {
        $schichtenNachDienst[(int)$zeile['DienstID']][] = $zeile;
    }

    $MeineDienste = SchichtIdArrayEinesHelfers($db_link, $HelferID);

    // Nur der aktuelle (Wurzel-)Dienst ist NIE faltbar. Alle anderen (Kind-,
    // Enkel-Dienste, ...) sind faltbar, aber nur wenn es mehr als einen gibt --
    // ein einzelner lohnt das Auf-/Zuklappen nicht.
    $andereAnzahl  = count($dienste) - ($AktuellerDienst !== null ? 1 : 0);
    $andereFoldable = $andereAnzahl > 1;

    foreach ($dienste as $DienstID => $d) {
        $istWurzel = ($DienstID === (int)$AktuellerDienst);
        $tiefe = $eigenePfadLaenge !== null ? (substr_count($d['DienstBaumPfad'], '/') - $eigenePfadLaenge) : 0;
        $foldable = $istWurzel ? false : $andereFoldable;

        _ZeigeDienstHeader($db_link, $DienstID, $d['Was'], $d['Wo'], $d['Info'], $d['Leiter'],
                            null, null, $o['HelferLevel'], $o['HelferLevel'], $o['modus'],
                            "ID:$DienstID ", $tiefe, $foldable);

        foreach (($schichtenNachDienst[$DienstID] ?? []) as $zeile) {
            _ZeigeSchichtZeile($zeile, $o['modus'], $MeineDienste, $o['HelferLevel'], $foldable);
        }
    }
}

/**
 * Drilldown-Modus: eigener Header + direkte Schichten, Kind-Dienste nur als
 * Navigations-Links (keine Schichten inline) -- für große Teilbäume.
 */
function _ZeigeDienstDrilldown($db_link, $AktuellerDienst, $Von, $Bis, $HelferID, array $o): void
{
    if ($AktuellerDienst !== null) {
        $dienst = GetEinzelDienst($db_link, $AktuellerDienst);
        if ($dienst) {
            _ZeigeDienstHeader($db_link, $dienst['DienstID'], $dienst['Was'], $dienst['Wo'], $dienst['Info'],
                                $dienst['Leiter'], null, null, $o['HelferLevel'], $o['HelferLevel'],
                                $o['modus'], "ID:{$dienst['DienstID']} ");
        }
    }

    $kinder = GetDiensteChildren($db_link, $AktuellerDienst);
    while ($row = mysqli_fetch_assoc($kinder)) {
        $zahlen = AlleBelegteSchichtenCountMitSurplus($db_link, -1, $row['DienstID'], true);
        $gesamt = AlleSchichtenCount($db_link, -1, $row['DienstID'], true);
        $ueberbelegt = $zahlen['ueberbelegt'] > 0 ? "[+{$zahlen['ueberbelegt']}]" : '';
        echo "<tr><th colspan='5' style='width:100%'>";
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?dienst=' . (int)$row['DienstID'] . '">';
        echo '&#128193; ID:' . (int)$row['DienstID'] . ' ' . htmlspecialchars($row['Was']);
        echo "</a> ({$zahlen['besetzt']}/{$gesamt}) {$ueberbelegt}</th></tr>";
    }

    if ($AktuellerDienst !== null) {
        $MeineDienste = SchichtIdArrayEinesHelfers($db_link, $HelferID);
        $schichten = AlleSchichtenImZeitbereich($db_link, $Von, $Bis, -1, $AktuellerDienst, false);
        while ($zeile = mysqli_fetch_array($schichten, MYSQLI_ASSOC)) {
            _ZeigeSchichtZeile($zeile, $o['modus'], $MeineDienste, $o['HelferLevel'], false);
        }
    }
}


// ============================================================================
// Dauer-Hilfsfunktionen
// ============================================================================
// Dauer ist in der DB eine TIME-Spalte ("HH:MM:SS"), im Formular aber zwei
// Zahlenfelder (Stunden/Minuten): <input type="time"> zeigt je nach Browser-
// Locale "12:00 AM" für 0 und ist für Dauern ungeeignet.

/** "HH:MM[:SS]" -> [Stunden, Minuten]. Leer/ungültig -> Defaults. */
function DauerZerlegen($dauer, int $defaultStunden = 1, int $defaultMinuten = 0): array
{
    if (is_string($dauer) && preg_match('/^(\d{1,3}):(\d{2})/', $dauer, $m)) {
        return [(int)$m[1], (int)$m[2]];
    }
    return [$defaultStunden, $defaultMinuten];
}

/** Stunden + Minuten aus dem Formular -> "HH:MM:00" für die TIME-Spalte (begrenzt auf 0-99 h / 0-59 min). */
function DauerAusFormular($stunden, $minuten): string
{
    $h = max(0, min(99, (int)$stunden));
    $m = max(0, min(59, (int)$minuten));
    return sprintf('%02d:%02d:00', $h, $m);
}

/** Von (beliebiges DateTime-Format, auch "…T…") + Dauer ("HH:MM:SS") -> "Y-m-d H:i:s". */
function DauerEndzeit(string $von, string $dauer): string
{
    [$h, $m] = DauerZerlegen($dauer, 0, 0);
    return (new DateTime($von))->add(new DateInterval("PT{$h}H{$m}M"))->format('Y-m-d H:i:s');
}

// ============================================================================
// Inline-Editiermodus (admin_edit)
// ============================================================================

/** Dauer für die Listenanzeige: "1h", "1h 30min", "45min"; 0 wird bewusst als "0h" sichtbar. */
function DauerKurz($dauer): string
{
    [$h, $m] = DauerZerlegen($dauer, 0, 0);
    if ($h > 0 && $m > 0) { return "{$h}h {$m}min"; }
    if ($h > 0)           { return "{$h}h"; }
    return $m > 0 ? "{$m}min" : "0h";
}

/** Ab wie vielen Suchtreffern (Diensten) wird NICHT mehr automatisch aufgeklappt. */
if (!defined('SUCHE_AUTOAUFKLAPPEN')) {
    define('SUCHE_AUTOAUFKLAPPEN', 3);
}

/**
 * <datalist> mit den Diensten -- gemeinsam für Legacy-Formulare und Übersichtssuche.
 * $mitID: Werte als "ID - Name" (zum Auswählen eines Dienstes; ausgewertet wird die ID),
 *         sonst nur der Name (Textfilter, stripos auf den Namen).
 * $ausser: Dienst samt Nachfahren weglassen (Elterndienst-Auswahl, Zyklus-Schutz).
 */
function AusgabeDienstDatalist($db_link, string $id, bool $mitID = true, $ausser = null): void
{
    $erg = ($ausser !== null) ? GetDiensteAuswahlbar($db_link, $ausser) : GetDienste($db_link);
    $namen = [];
    echo '<datalist id="' . htmlspecialchars($id, ENT_QUOTES) . '">';
    while ($z = mysqli_fetch_array($erg, MYSQLI_ASSOC)) {
        if ($mitID) {
            echo '<option value="' . (int)$z['DienstID'] . ' - ' . htmlspecialchars($z['Was'], ENT_QUOTES) . '">';
        } elseif (!isset($namen[$z['Was']])) {
            $namen[$z['Was']] = true;
            echo '<option value="' . htmlspecialchars($z['Was'], ENT_QUOTES) . '">';
        }
    }
    mysqli_free_result($erg);
    echo '</datalist>';
}

// ============================================================================
// Inline-Editiermodus (admin_edit)
// ============================================================================
// Die Editierfelder gibt es nicht im HTML: sie werden erst beim Klick auf den
// Bleistift per JavaScript (helferdb.js) aus data-Attributen gebaut. Sonst
// enthielte die Seite hunderte versteckte Felder -- das ist langsam und sprengt
// das PHP-Limit max_input_vars (Standard 1000) beim Abschicken.

/** Alle Dienste/Helfer/Level einmal pro Seite laden statt pro Zeile. */
function _AdminEditDaten($db_link): array
{
    static $daten = null;
    if ($daten !== null) { return $daten; }
    $daten = ['helfer' => [], 'dienste' => [], 'level' => alleHelferLevel($db_link)];
    $erg = HelferListe($db_link);
    while ($z = mysqli_fetch_assoc($erg)) { $daten['helfer'][(int)$z['HelferID']] = $z['Name']; }
    mysqli_free_result($erg);
    $erg = GetDienste($db_link);
    while ($z = mysqli_fetch_assoc($erg)) { $daten['dienste'][(int)$z['DienstID']] = $z; }
    mysqli_free_result($erg);
    return $daten;
}

/** Gemeinsame <datalist>s für Leiter/Elterndienst, Suchvorschläge und die HelferLevel-Liste für das JS. */
function _AdminEditDatalists($db_link): void
{
    $d = _AdminEditDaten($db_link);
    echo '<datalist id="dl-eltern-dienste">';
    foreach ($d['dienste'] as $id => $dienst) {
        echo '<option value="' . $id . ' - ' . htmlspecialchars($dienst['Was'], ENT_QUOTES) . '">';
    }
    echo '</datalist><datalist id="dl-helfer">';
    foreach ($d['helfer'] as $id => $name) {
        echo '<option value="' . $id . ' - ' . htmlspecialchars($name, ENT_QUOTES) . '">';
    }
    echo '</datalist>';
    echo '<script>var DIENST_EDIT_LEVEL = ' . json_encode((object)$d['level'],
         JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
}

/** data-Attribute eines Dienstes für den Editiermodus (am <td id="DD…">). */
function _DienstEditAttribute($db_link, $DienstID): string
{
    $e = _AdminEditDaten($db_link);
    $d = $e['dienste'][(int)$DienstID] ?? null;
    if (!$d) { return ''; }
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $leiter = $d['Leiter'] ? ((int)$d['Leiter'] . ' - ' . ($e['helfer'][(int)$d['Leiter']] ?? '?')) : '';
    $eltern = $d['ElternDienstID']
        ? ((int)$d['ElternDienstID'] . ' - ' . ($e['dienste'][(int)$d['ElternDienstID']]['Was'] ?? '?')) : '';
    return " data-was='" . $h($d['Was']) . "' data-wo='" . $h($d['Wo']) . "' data-info='" . $h($d['Info'])
         . "' data-leiter='" . $h($leiter) . "' data-eltern='" . $h($eltern)
         . "' data-level='" . (int)$d['HelferLevel'] . "'";
}

/**
 * Setzt/entfernt Query-Parameter einer URL (Pfad + Query, z.B. $_SERVER['REQUEST_URI']),
 * alle anderen Parameter (Suche, Zeitbereich, ...) bleiben erhalten.
 */
function UrlMitParametern(string $uri, array $setzen = [], array $entfernen = []): string
{
    $teile = parse_url($uri);
    $q = [];
    parse_str($teile['query'] ?? '', $q);
    foreach ($entfernen as $k) { unset($q[$k]); }
    foreach ($setzen as $k => $v) { $q[$k] = $v; }
    return ($teile['path'] ?? '') . ($q ? '?' . http_build_query($q) : '');
}

/**
 * Liest und prüft die Felder einer Inline-Schicht (edit_schicht[ID] bzw. neue_schicht[DienstID] aus $_POST).
 * Gibt von/bis ("Y-m-d H:i"), soll, dauer ("HH:MM:00") und ggf. eine Fehlermeldung zurück.
 */
function SchichtFormularLesen(array $f): array
{
    $von   = str_replace('T', ' ', trim($f['von'] ?? ''));
    $bis   = str_replace('T', ' ', trim($f['bis'] ?? ''));
    $soll  = (int)($f['soll'] ?? 0);
    $dauer = DauerAusFormular($f['dauer_h'] ?? 0, $f['dauer_m'] ?? 0);
    $fehler = null;
    if ($von === '' || $bis === '' || strtotime($bis) <= strtotime($von)) {
        $fehler = 'Das Ende muss nach dem Beginn liegen.';
    } elseif ($soll < 1) {
        $fehler = 'Die Anzahl (Soll) muss mindestens 1 sein.';
    }
    return compact('von', 'bis', 'soll', 'dauer', 'fehler');
}

/**
 * admin_edit: leere Zeile am Ende der Schichten eines Dienstes, Bleistift öffnet die Inline-Eingabe für eine
 * neue Schicht. Vorgaben aus der letzten angezeigten Schicht: Beginn = deren Ende (Anschlussschicht),
 * gleiche Dauer und Anzahl; "Ende aus Dauer" ist vorgewählt. Für Serien: speichern, Bleistift, speichern, ...
 */
/**
 * Werte einer Anschlussschicht: Beginn = Ende der gegebenen Schicht, gleiche Dauer und Anzahl.
 * Ist die gespeicherte Dauer 0/leer (Altdaten), wird sie aus Von/Bis der Schicht abgeleitet.
 * Rückgabe: von, bis ("Y-m-d H:i:s"), dauer ("HH:MM:00"), soll.
 */
function NachfolgeSchichtWerte(string $von, string $bis, $dauer, $soll): array
{
    [$h, $m] = DauerZerlegen($dauer, 0, 0);
    if ($h === 0 && $m === 0) {
        $min = max(0, (int)round((strtotime($bis) - strtotime($von)) / 60));
        $h = intdiv($min, 60);
        $m = $min % 60;
    }
    $dauerNeu = DauerAusFormular($h, $m);
    $vonNeu   = (new DateTime($bis))->format('Y-m-d H:i:s');
    return ['von' => $vonNeu, 'bis' => DauerEndzeit($vonNeu, $dauerNeu), 'dauer' => $dauerNeu, 'soll' => max(1, (int)$soll)];
}

function _ZeigeNeueSchichtZeile(array $letzte): void
{
    $dienst = (int)$letzte['DienstID'];
    if (!empty($letzte['leer'])) {
        // Dienst ohne Schicht: nichts, woran man anschließen könnte -> Beginn leer, 1 h, 1 Person
        $vonV = '';
        $bisV = '';
        $dh = 1; $dm = 0; $soll = 1;
    } else {
        if (!isset($letzte['VonInput'], $letzte['BisInput'])) { return; }   // ohne Rohwerte kein Inline-Editieren
        // Vorbelegung: Anschluss an die letzte Schicht des Dienstes
        $w = NachfolgeSchichtWerte($letzte['VonInput'], $letzte['BisInput'], $letzte['Dauer'] ?? null, $letzte['Soll']);
        [$dh, $dm] = DauerZerlegen($w['dauer'], 0, 0);
        $vonV = substr(str_replace(' ', 'T', $w['von']), 0, 16);
        $bisV = substr(str_replace(' ', 'T', $w['bis']), 0, 16);
        $soll = (int)$w['soll'];
    }
    echo "<tr class='collapsible-content' id='SN{$dienst}' data-neu='1' data-dienst='{$dienst}'"
       . " data-von='{$vonV}' data-bis='{$bisV}'"
       . " data-dh='{$dh}' data-dm='{$dm}' data-soll='{$soll}'>";
    echo "<td class='view' colspan='4'><i>Neue Schicht anlegen</i></td>";
    echo "<td class='view' style='width:10%;white-space:nowrap' onclick='event.stopPropagation()'>"
       . "<button type='button' title='Neue Schicht anlegen' onclick=\"toggleEditSchicht('N{$dienst}')\">&#9998;</button></td>";
    echo "</tr>\n";
}

/**
 * Admin-Übersicht mit allen Diensten: ergänzt Dienste ohne Schicht im Zeitbereich (als Zeile mit 'leer' => true)
 * und sortiert hierarchisch (Eltern direkt vor ihren Kindern, nach Namen), danach nach Beginn.
 * Beachtet das angezeigte HelferLevel wie die Schicht-Abfrage.
 */
function _MitLeerenDiensten($db_link, array $zeilen, $HelferLevelAnzeige): array
{
    $e = _AdminEditDaten($db_link);
    $vorhanden = [];
    foreach ($zeilen as $z) { $vorhanden[(int)$z['DienstID']] = true; }
    foreach ($e['dienste'] as $id => $d) {
        if (isset($vorhanden[$id])) { continue; }
        if ($HelferLevelAnzeige != -1 && (int)$d['HelferLevel'] !== (int)$HelferLevelAnzeige) { continue; }
        $zeilen[] = ['SchichtID' => null, 'DienstID' => $id, 'Was' => $d['Was'], 'leer' => true];
    }
    // Sortierschlüssel: Namen von der Wurzel bis zum Dienst. Das Trennzeichen \x01 sortiert vor allen
    // druckbaren Zeichen, Kinder stehen so direkt hinter ihrem Elterndienst.
    $cache = [];
    $schluessel = function (int $id) use ($e, &$cache) {
        if (isset($cache[$id])) { return $cache[$id]; }
        $namen = [];
        $cur = $id;
        for ($n = 0; $cur > 0 && isset($e['dienste'][$cur]) && $n < 12; $n++) {   // 12 = Schutz vor Zyklen
            array_unshift($namen, $e['dienste'][$cur]['Was']);
            $cur = (int)$e['dienste'][$cur]['ElternDienstID'];
        }
        return $cache[$id] = implode("\x01", $namen);
    };
    usort($zeilen, function ($a, $b) use ($schluessel) {
        $c = strcasecmp($schluessel((int)$a['DienstID']), $schluessel((int)$b['DienstID']));
        if ($c !== 0) { return $c; }
        if ((int)$a['DienstID'] !== (int)$b['DienstID']) { return (int)$a['DienstID'] <=> (int)$b['DienstID']; }
        return strcmp($a['VonInput'] ?? '', $b['VonInput'] ?? '');
    });
    return $zeilen;
}

/** Leiste für den Massen-Edit: ein Feld für alle markierten Dienste setzen (Logik in helferdb.js / AdminDienste.php). */
function _ZeigeMassenEditLeiste(): void
{
    echo '<table class="commontable"><tr><td style="white-space:nowrap">';
    echo '<label title="Alle angezeigten Dienste markieren"><input type="checkbox" style="width:unset" onclick="massenAlleUmschalten(this.checked)"> alle</label> ';
    echo '<span id="massen-zaehler">0 markiert</span> &nbsp; Für die markierten Dienste setzen: ';
    echo '<select id="massen-feld" name="massen_feld" style="width:auto" onchange="massenFeldWechsel()">'
       . '<option value="eltern">Elterndienst</option><option value="leiter">Ansprechpartner</option>'
       . '<option value="wo">Ort</option><option value="level">HelferLevel</option></select> ';
    echo '<span id="massen-wert"></span> ';
    echo '<button type="submit" id="massen-anwenden" name="MassenEdit" value="1" title="Auf alle markierten Dienste anwenden" onclick="return massenPruefen()">&#10004;</button>';
    echo '</td></tr></table>';
}
