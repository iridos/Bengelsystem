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
    <body>
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

// Auswahl Tag oberhalb der Dienstetabelle
if (isset($_GET['ZeitBereich'])) {
    $ZeitBereich = $_GET['ZeitBereich'];
} else {
    $ZeitBereich = 0;
}

function HelferAuswahlButton($db_link, $AliasHelferID)
{
    echo '<b>Helfer w&auml;hlen:<b> <form style="display:inline-block;" method=post><select style="height:33px;width:350px;" name="AliasHelferID" id="AliasHelferID" onchange="submit()">';
    $db_erg = HelferListe($db_link);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        if ($AliasHelferID != $zeile['HelferID']) {
            echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</optionen>";
        } else {
                echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</optionen>";
        }
    }
    echo '</select></form>';
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
    // Wenn es ein Admin wird ZielHelferID AliasHelferID, sonst HelferID
        if ($AdminStatus == 1 && isset($_POST['AliasHelferID'])){
            $_SESSION["AliasHelferID"] = $_POST['AliasHelferID'];
        }
        header("Location: " . $_SERVER['PHP_SELF']);
    }
}
