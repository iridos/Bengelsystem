<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
// zeigt login-Seite an, wenn keine Session besteht
require '_login.php';

if ($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}
?>
<!doctype html>
<html>
<head>
  <title><?php echo EVENTNAME ?> Alle Schichten</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />

  <script src="js/jquery-3.7.1.min.js" type="text/javascript"></script>
  <script src="js/helferdb.js" type="text/javascript"></script>
  <script> collapse_table_rows();
 </script>
</head>
<body>
  <button name="BackHelferdaten" value="1"  onclick="window.location.href = 'AdminHelferUebersicht.php';">
  <b>&larrhk;</b>
  </button>
  <?php echo "<b>" . EVENTNAME . "</b>"; ?>
  <h1> Alle Schichten / Schichten hinzuf&uuml;gen </h1>
  <div style="width: 100%;">
  <?php

/// Detailinformation zu ausgewaehlten Schicht Holen
////////////////////////////////////////////////////////
    if (isset($_POST['CloseInfo'])) {
        unset($InfoMeineSchichtID);
        unset($InfoAlleSchichtID);
    }
// wird nie gesetzt
//if (isset($_POST['InfoMeineSchichtID'])) {
    function SchichtInfo($SchichtID, &$Was, &$Wo, &$Dauer, &$Leiter, &$LeiterHandy, &$LeiterEmail, &$Info)
    {
          $db_link = ConnectDB();
    //    $InfoMeineSchichtID = $_POST['InfoMeineSchichtID'];
        unset($InfoAlleSchichtID);
        //echo "<b>". $SchichtID . "</b><br>";

        $zeile = DetailSchicht($db_link, $SchichtID);

        $Was = $zeile['Was'];
        $Wo = $zeile['Wo'];
        $Dauer = $zeile['Dauer'];
        $Leiter = $zeile['Name'];
        $LeiterHandy =  $zeile['Handy'];
        $LeiterEmail =  $zeile['Email'];
        $Info = $zeile['Info'];
        $db_link->close();
    }

// wird nur mit anderer Datei DetailsSchichten.php verwendet, nicht hier
//if (isset($_GET['InfoAlleSchichtID'])) {
//    $InfoAlleSchichtID = $_GET['InfoAlleSchichtID'];
//    unset($InfoMeineSchichtID);
//    //echo "<b>". $SchichtID . "</b><br>";
//
//    $zeile = DetailSchicht($db_link, $InfoAlleSchichtID);
//
//    $Was = $zeile['Was'];
//    $Wo = $zeile['Wo'];
//    $Dauer = $zeile['Dauer'];
//    $Leiter = $zeile['Name'];
//    $LeiterHandy =  $zeile['Handy'];
//    $LeiterEmail =  $zeile['Email'];
//    $Info = $zeile['Info'];
//
//
//
//    // Beteiligte Helfer Holen
//    $db_erg = BeteiligteHelfer($db_link, $InfoAlleSchichtID);
//
//
//    $x = 0;
//
//    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
//        $MitHelferID[$x] = $zeile['HelferID'];
//        $MitHelfer[$x] = $zeile['Name'];
//        $MitHelferHandy[$x] = $zeile['Handy'];
//        $x++;
//    }
//}

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

    if (isset($_POST['AliasHelferID'])) {
        $AliasHelferID = $_POST['AliasHelferID'];
    } elseif (isset($_SESSION["AliasHelferID"])) {
        $AliasHelferID = $_SESSION["AliasHelferID"];
    } else {
        HelferAuswahlButton($db_link, $AliasHelferID);
        echo "<p>Erst Helfer auswählen</p>";
        exit;
    }
    HelferAuswahlButton($db_link, $AliasHelferID);

    $_SESSION["AliasHelferID"] = $AliasHelferID;
    $AdminID = $_SESSION["AdminID"];

    $db_erg = Helferdaten($db_link, $AliasHelferID);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $HelferName = $zeile['Name'];
        $AliasHelferLevel = $zeile['HelferLevel'];
    }

// Helferliste Anzeigen
////////////////////////////////////////////////////////

    ?>


<form method="post" action="#Info">  
<?php



// Neu Schicht fuer Helfer Eintragen
///////////////////////////////////////////////////////////
if (isset($_POST['plusschicht'])) {
    $messages = [];
    $SchichtID = $_POST['plusschicht'];
    // Nutzer hat hier zuletzt etwas geändert und wir klappen das deshalb auf,
    // indem wir unten target=active setzen
    $_SESSION["SchichtIdAktiv"] = $SchichtID;
    if (empty($messages)) {
        // Helfer Schicht zuweisen
        $db_erg = HelferSchichtZuweisen($db_link, $AliasHelferID, $SchichtID, $AdminID);

        // Erfolg vermelden und Skript beenden, damit Formular nicht erneut ausgegeben wird
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
    }
}

if (isset($_POST['minusschicht'])) {
        // Mich aus Schicht entfernen
        $messages = [];

        $SchichtID = $_POST['minusschicht'];
        // Nutzer hat hier zuletzt etwas geaenndert und wir klappen das deshalb auf:
        $_SESSION["SchichtIdAktiv"] = $SchichtID;

    if (empty($messages)) {
        // Helfer aus Schicht entfernen
        $db_erg = HelferVonSchichtLoeschen_SchichtID($db_link, $AliasHelferID, $SchichtID, $AdminID);
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
                echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}

/// Ausgabe auf Deutsch umstellen
/////////////////////////////////////////////////////////////////////////

    DatenbankAufDeutsch($db_link);


// Zusammenfassung Eigener Schichten
 $db_erg = SchichtenSummeEinesHelfers($db_link, $AliasHelferID);
 $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);

    //"Dienstplan von"
    echo '<table class="commontable"><tr class="header"><th onclick="window.location.href=\'AdminMeineSchichten.php\'">';
    echo '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">' . "Dienstplan von $HelferName: ";
    echo $zeile['Anzahl'];
    echo " Schichten, ";
    echo $zeile['Dauer'] / 3600;
    echo " Stunden)";
    echo '</th></tr></table><br><br>';
/// Schichten Auswahl
////////////////////////////////////////////////////////
$addschicht = $_SESSION["addschicht"];
$dienstsort = $_SESSION["dienstsort"];


//addschicht und dienst-sort sollten wohl nach Diensten bzw Tagen sortieren
//addschicht wird gerade nie gesetzt, dienst-sort damit auch nicht
if (isset($_POST['addschicht']) && $_POST['addschicht'] == '1') {
    $addschicht = '1';
    $dienstsort = '1';
}
if (isset($_POST['addschicht']) && $_POST['addschicht'] == '2') {
    $addschicht = '2';
    $dienstsort = '2';
}
if (isset($_POST['addschicht']) && $_POST['addschicht'] == '0') {
    $addschicht = '0';
}

$_SESSION["addschicht"] = $addschicht;
$_SESSION["dienstsort"] = $dienstsort;

//echo "<br>Detail=".$addschicht."<br>";

if ($addschicht == '0') {
    echo "<p><b>Schichten Hinzufügen geordnet nach</b>";
    echo "<button name='addschicht' value='1'>Tage</button>";
    echo "<button name='addschicht' value='2'>Dienste</button></p>";
}


if ($addschicht != '0') { // addschicht soll Darstellung nach Tagen oder Diensten sortieren, macht es aber nicht
    echo '<table class="commontable">';
    require('_zeitbereich.php');
    $Bereich = AusgabeZeitbereichZeile($start_date, $ZeitBereich, $TageNamenDeutsch, $_SERVER['PHP_SELF']);
    $MeinVon = $Bereich['MeinVon'];
    $MeinBis = $Bereich['MeinBis'];
    $db_erg = AlleSchichtenImZeitbereich($db_link, $MeinVon, $MeinBis, $AliasHelferLevel);

    // fuer Anzahlanzeige in Ueberschrift
    $iAlleSchichtenCount = AlleSchichtenCount($db_link);
    $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link);
    echo '</table>';
        echo "<button type='button' onclick='expand_all_table_rows();'>Alles Ausklappen</button>";

    // "Alle Schichten der Con"
    echo '<table  class="commontable">';
    echo "<tr class='infoheader'>";
    echo "<th colspan='5'>Alle Schichten der Con (Besetzt/Gesamt) " . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . "</th></tr>";

    $alleHelferLevel = alleHelferLevel($db_link);
    foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
        $meine = "";
        if ($HelferLevelIteration == $AliasHelferLevel) {
            $meine = " &leftarrow; mein Level, Schichten werden unten angezeigt";
        }
        $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelIteration);
        $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link, $HelferLevelIteration);
        echo "<tr class='infoheader'><th colspan='5' >&nbsp;&nbsp; &rightarrow; Schichten  $HelferLevelBeschreibung (Besetzt/Gesamt) (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")  $meine</th></tr>";
    }


    $OldTag = "";
    $OldWas = "";
    // um Zeilen mit von mir belegten Schichten hervorzuheben
    $MeineDienste = SchichtIdArrayEinesHelfers($db_link, $AliasHelferID);
    //print_r($MeineDienste);

    echo '</table>';
    // Tabelle mit allen Diensten und Schichten
    echo '<table  class="commontable collapsible">';
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        if ($dienstsort == '1') { // dienst-sort wird momentan nie gesetzt, also immer else-Teil ausgeführt
            $Tag = $zeile['Tag'];

            if ($Tag != $OldTag) {
                echo "<tr class='header'><th colspan='5' >";
                echo $Tag;
                echo "</th></tr>";
                $OldTag = $Tag;
            }
        } else {
            $Was = $zeile['Was'];

            if ($Was != $OldWas) { // Header ausgeben, wenn der Dienst nicht mehr der selbe ist
                // + in <span> becomes - when rows are opened
                echo "<tr class='header'><th  colspan='5' style='width:100%'><span>+</span> ";
                $SchichtID = $zeile['SchichtID'];
                $DienstID = $zeile['DienstID'];
                $iAlleSchichtenCount = AlleSchichtenCount($db_link, $AliasHelferLevel, $DienstID);
                $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link, $AliasHelferLevel, $DienstID);
                echo "$Was ($iBelegteSchichtenCount/$iAlleSchichtenCount) <!-- Abfrage $AliasHelferLevel, $DienstID -->";
                echo "</th>";
                echo "</tr>";
                SchichtInfo($SchichtID, $InfoWas, $InfoWo, $InfoDauer, $Leiter, $LeiterHandy, $LeiterEmail, $Info);
                if (true) {
                    echo "<tr><td colspan=5 style='background:lightblue'>";
                    echo "<b>Beschreibung:</b> $Info <br><br>";
                    echo "<b>Ort:</b> $InfoWo <br>";
                //echo "<b>Dauer:</b> $InfoDauer<br>"; // verschieden je nach Einzelschicht
                    echo "<b>Ansprechparter:</b>" . $Leiter . ", ";
                    echo $LeiterHandy . ", ";
                    echo "$LeiterEmail";
                    echo "</td></td></tr>\n";
                }
                $OldWas = $Was;
            }
        }
        $Color = "red";
        if ($zeile['Ist'] > 0) {
            $Color = "yellow";
        }
        if ($zeile['Ist'] >= $zeile['Soll']) {
            $Color = "green";
        }
        $Von = $zeile['Ab'];
        $Bis = $zeile['Bis'];
        if (substr($Von, 0, 2) == substr($Bis, 0, 2)) {
            $Bis = substr($Bis, 2);
        }
        $Von = substr($Von, 2);

              // Meine Schichten gruen einfaerben
        if (in_array($zeile['SchichtID'], $MeineDienste)) {
             $rowstyle = ' style="background-color:lightgreen" ';
             $regtext  = '<br><center>Meine!</center>';
        } else {
            // dummy-style, um SchichtID unsichtbar im Tag anzuzeigen
            $rowstyle = 'dbinfo="SchichtID:' . $zeile['SchichtID'] . ';helferlvl:' . $HelferLevel . '" ';
            $regtext  = '';
        }
        if (isset($_SESSION["SchichtIdAktiv"]) && $_SESSION["SchichtIdAktiv"] == $zeile['SchichtID']) {
            $rowstyle = $rowstyle . " target='active' "; // dont collapse when the user did something
        }

                echo '<tr ' . $rowstyle . 'onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID=' . $zeile['SchichtID'] . '#Info\';" >';

        if ($dienstsort == '1') {
            echo "<td>" . $zeile['Was'] . "</td>";
        } else {
            echo "<td>" . $zeile['Tag'] . "</td>";
        }
        echo "<td>" . $Von . "</td>";
        echo "<td>" . $Bis . "</td>";
        echo "<td bgcolor='" . $Color . "'>" . $zeile['Ist'] . "/";
        echo "" . $zeile['Soll'] . "</td>";
        // durch space:nowrap wird ein Umbruch zwischen den Buttons verhindert
        // in Kombi mit width:1% wird immer der minimale Platz für die Spalte belegt
        // width:200px oder max-width:200px hat zu viel weissem Platz rechts und enge links gefuehrt
        echo "<td style='width:10%;white-space:nowrap'><button name='plusschicht' value='" . $zeile['SchichtID'] . "'>+</button>";
        echo "&nbsp;&nbsp;<button name='minusschicht' value='" . $zeile['SchichtID'] . "'>&ndash;</button> $regtext" . "</td>";
        echo "</tr>\n";
    }
    echo "</table>";
}







mysqli_free_result($db_erg);


?>

 </form>
 </div>

 </body>
</html>
